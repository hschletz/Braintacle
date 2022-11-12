<?php

/**
 * Tests for Model\Client\Client
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Model\Test\Client;

use Database\Table\ClientConfig;
use Database\Table\GroupMemberships;
use DateTime;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Model\Client\Client;
use Model\Client\CustomFieldManager;
use Model\Client\CustomFields;
use Model\Group\GroupManager;
use Model\Package\Assignment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class ClientTest extends \Model\Test\AbstractTest
{
    protected static $_tables = [
        'AndroidInstallations',
        'ClientsAndGroups',
        'WindowsProductKeys',
        'WindowsInstallations',
        'DuplicateSerials',
        'DuplicateAssetTags',
        'ClientConfig',
        'Packages',
        'PackageHistory',
        'GroupMemberships',
    ];

    public function testObjectProperties()
    {
        $model = $this->getModel();
        $this->assertInstanceOf('ArrayAccess', $model);
        $this->assertTrue(method_exists($model, 'exchangeArray'));
    }

    public function testOffsetGetExistingProperty()
    {
        $model = new Client(['Key' => 'value']);
        $this->assertEquals('value', $model['Key']);
    }

    public function testOffsetGetAndroidNotNull()
    {
        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new \Model\Client\Client(array('Id' => 3));
        $model->setServiceLocator($serviceManager);

        $android = $model['Android'];
        $this->assertInstanceOf('Model\Client\AndroidInstallation', $android);
        $this->assertEquals(
            [
                'Country' => 'country',
                'JavaVm' => 'java_vm',
                'JavaInstallationDirectory' => 'java_installation_directory',
                'JavaClassPath' => 'java_class_path',
            ],
            $android->getArrayCopy()
        );
        $this->assertSame($android, $model['Android']); // cached result
    }

    public function testOffsetGetAndroidNull()
    {
        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new \Model\Client\Client(array('Id' => 2));
        $model->setServiceLocator($serviceManager);

        $this->assertNull($model['Android']);
        $this->assertNull($model['Android']); // cached result
    }

    public function testOffsetGetWindowsNotNull()
    {
        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new \Model\Client\Client(array('Id' => 2));
        $model->setServiceLocator($serviceManager);

        $windows = $model['Windows'];
        $this->assertInstanceOf('Model\Client\WindowsInstallation', $windows);
        $this->assertEquals(
            array(
                'Workgroup' => 'workgroup2',
                'UserDomain' => 'userdomain2',
                'Company' => 'company2',
                'Owner' => 'owner2',
                'ProductKey' => 'product_key2',
                'ProductId' => 'product_id2',
                'ManualProductKey' => 'manual_product_key2',
                'CpuArchitecture' => 'cpu_architecture2',
            ),
            $windows->getArrayCopy()
        );
        $this->assertSame($windows, $model['Windows']); // cached result
    }

    public function testOffsetGetWindowsNull()
    {
        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new \Model\Client\Client(array('Id' => 3));
        $model->setServiceLocator($serviceManager);

        $this->assertNull($model['Windows']);
        $this->assertNull($model['Windows']); // cached result
    }

    public function testOffsetGetCustomFields()
    {
        $customFields = $this->createStub(CustomFields::class);

        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->once())->method('read')->with(2)->willReturn($customFields);

        $model = $this->getModel(array('Model\Client\CustomFieldManager' => $customFieldManager));
        $model->id = 2;
        $this->assertEquals($customFields, $model->customFields);
        $this->assertEquals($customFields, $model->customFields); // cached result
    }

    public function testOffsetGetRegistry()
    {
        $model = new \Model\Client\Client(array('Registry.Content' => 'something'));
        $this->assertEquals('something', $model['Registry.Something']);
    }

    public function offsetGetBlacklistedProvider()
    {
        return array(
            array('IsSerialBlacklisted', 'Serial', 'serial_good', false),
            array('IsSerialBlacklisted', 'Serial', 'serial_bad', true),
            array('IsAssetTagBlacklisted', 'AssetTag', 'assettag_good', false),
            array('IsAssetTagBlacklisted', 'AssetTag', 'assettag_bad', true),
        );
    }

    /**
     * @dataProvider offsetGetBlacklistedProvider
     */
    public function testOffsetGetBlacklisted($index, $initialIndex, $initialValue, $result)
    {
        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );
        $model = new \Model\Client\Client(array($initialIndex => $initialValue));
        $model->setServiceLocator($serviceManager);
        $this->assertSame($result, $model[$index]);
        $this->assertSame($result, $model[$index]); // cached result
    }

    public function testOffsetGetItems()
    {
        /** @var MockObject|Client */
        $model = $this->createPartialMock(Client::class, ['getItems']);
        $model->expects($this->once())->method('getItems')->with('Type')->willReturn('items');
        $this->assertEquals('items', $model['type']);
        $this->assertEquals('items', $model['type']); // cached result
    }

    public function getDefaultConfigProvider()
    {
        // All options have a default, so the global value can never be NULL.
        return array(
            array('inventoryInterval', -1, array(0), -1), // global value -1 precedes
            array('inventoryInterval', 0, array(-1), 0), // global value 0 precedes
            array('inventoryInterval', 1, array(), 1), // no group values, default to global value
            array('inventoryInterval', 1, array(null), 1), // no group values, default to global value
            array('inventoryInterval', 1, array(2, null, 3), 2), // smallest group value
            array('inventoryInterval', 4, array(2, 3, null), 2), // smallest group value
            array('contactInterval', 1, array(2, 3, null), 2),
            array('contactInterval', 1, array(), 1),
            array('downloadMaxPriority', 1, array(2, 3, null), 2),
            array('downloadMaxPriority', 1, array(), 1),
            array('downloadTimeout', 1, array(2, 3, null), 2),
            array('downloadTimeout', 1, array(), 1),
            array('downloadPeriodDelay', 3, array(1, 2, null), 2),
            array('downloadPeriodDelay', 1, array(), 1),
            array('downloadCycleDelay', 3, array(1, 2, null), 2),
            array('downloadCycleDelay', 1, array(), 1),
            array('downloadFragmentDelay', 3, array(1, 2, null), 2),
            array('downloadFragmentDelay', 1, array(), 1),
            array('packageDeployment', 0, array(1), 0),
            array('packageDeployment', 1, array(), 1),
            array('packageDeployment', 1, array(null, 1), 1),
            array('packageDeployment', 1, array(0, 1), 0),
            array('scanSnmp', 0, array(1), 0),
            array('scanSnmp', 1, array(), 1),
            array('scanSnmp', 1, array(null, 1), 1),
            array('scanSnmp', 1, array(0, 1), 0),
            array('allowScan', 0, array(1), 0),
            array('allowScan', 1, array(), 1),
            array('allowScan', 2, array(null, 1), 1),
            array('allowScan', 2, array(0, 1), 0),
        );
    }

    /**
     * @dataProvider getDefaultConfigProvider
     */
    public function testGetDefaultConfig($option, $globalValue, $groupValues, $expectedValue)
    {
        $globalOption = (($option == 'allowScan') ? 'scannersPerSubnet' : $option);

        $config = $this->createMock('Model\Config');
        $config->method('__get')->with($globalOption)->willReturn($globalValue);

        $groups = array();
        foreach ($groupValues as $groupValue) {
            $group = $this->createMock('Model\Group\Group');
            $group->method('getConfig')->with($option)->willReturn($groupValue);
            $groups[] = $group;
        }

        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->method('get')->with('Model\Config')->willReturn($config);

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroups', '__destruct']);
        $model->method('offsetGet')->willReturn(42);
        $model->method('getGroups')->willReturn($groups);
        $model->setServiceLocator($serviceManager);

        $this->assertSame($expectedValue, $model->getDefaultConfig($option));
    }

    public function testGetDefaultConfigCache()
    {
        $config = $this->createMock('Model\Config');
        $config->expects($this->exactly(2))
               ->method('__get')
               ->withConsecutive(array('option1'), array('option2'))
               ->willReturnOnConsecutiveCalls('value1', 'value2');

        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->expects($this->exactly(2))->method('get')->with('Model\Config')->willReturn($config);

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroups', '__destruct']);
        $model->expects($this->exactly(3))->method('offsetGet')->willReturn(42);
        $model->expects($this->exactly(2))->method('getGroups')->willReturn(array());
        $model->setServiceLocator($serviceManager);

        $this->assertEquals('value1', $model->getDefaultConfig('option1'));
        $this->assertEquals('value1', $model->getDefaultConfig('option1')); // from cache
        $this->assertEquals('value2', $model->getDefaultConfig('option2')); // non-cached value to test group cache
    }

    public function testGetAllConfigWithNonNullValues()
    {
        $model = $this->createPartialMock(Client::class, ['getConfig']);
        $model->method('getConfig')->willReturnMap([
            ['contactInterval', 1],
            ['inventoryInterval', 2],
            ['downloadPeriodDelay', 3],
            ['downloadCycleDelay', 4],
            ['downloadFragmentDelay', 5],
            ['downloadMaxPriority', 6],
            ['downloadTimeout', 7],
            ['scanThisNetwork', 'network'],
            // The following options can only be 0 or NULL
            ['packageDeployment', 0],
            ['allowScan', 0],
            ['scanSnmp', 0],
        ]);

        $this->assertSame(
            [
                'Agent' => [
                    'contactInterval' => 1,
                    'inventoryInterval' => 2,
                ],
                'Download' => [
                    'packageDeployment' => 0,
                    'downloadPeriodDelay' => 3,
                    'downloadCycleDelay' => 4,
                    'downloadFragmentDelay' => 5,
                    'downloadMaxPriority' => 6,
                    'downloadTimeout' => 7,
                ],
                'Scan' => [
                    'allowScan' => 0,
                    'scanSnmp' => 0,
                    'scanThisNetwork' => 'network',
                ],
            ],
            $model->getAllConfig()
        );
    }

    public function testGetAllConfigWithNullValues()
    {
        $model = $this->createPartialMock(Client::class, ['getConfig']);
        $model->method('getConfig')->willReturn(null);

        $this->assertSame(
            [
                'Agent' => [
                    'contactInterval' => null,
                    'inventoryInterval' => null,
                ],
                'Download' => [
                    'packageDeployment' => 1,
                    'downloadPeriodDelay' => null,
                    'downloadCycleDelay' => null,
                    'downloadFragmentDelay' => null,
                    'downloadMaxPriority' => null,
                    'downloadTimeout' => null,
                ],
                'Scan' => [
                    'allowScan' => 1,
                    'scanSnmp' => 1,
                    'scanThisNetwork' => null,
                ],
            ],
            $model->getAllConfig()
        );
    }

    public function testGetExplicitConfigWithNonNullValues()
    {
        $model = $this->createPartialMock(Client::class, ['getConfig']);
        $model->method('getConfig')->willReturnMap([
            ['contactInterval', 0],
            ['inventoryInterval', 1],
            ['downloadPeriodDelay', 2],
            ['downloadCycleDelay', 3],
            ['downloadFragmentDelay', 4],
            ['downloadMaxPriority', 5],
            ['downloadTimeout', 6],
            ['scanThisNetwork', 'network'],
            // The following options can only be 0 or NULL
            ['packageDeployment', 0],
            ['allowScan', 0],
            ['scanSnmp', 0],
        ]);

        $this->assertSame(
            [
                'contactInterval' => 0,
                'inventoryInterval' => 1,
                'packageDeployment' => 0,
                'downloadPeriodDelay' => 2,
                'downloadCycleDelay' => 3,
                'downloadFragmentDelay' => 4,
                'downloadMaxPriority' => 5,
                'downloadTimeout' => 6,
                'allowScan' => 0,
                'scanSnmp' => 0,
                'scanThisNetwork' => 'network',
            ],
            $model->getExplicitConfig()
        );
    }

    public function testGetExplicitConfigWithNullValues()
    {
        $model = $this->createPartialMock(Client::class, ['getConfig']);
        $model->method('getConfig')->willReturn(null);

        $this->assertSame([], $model->getExplicitConfig());
    }

    public function getEffectiveConfigProvider()
    {
        return array(
            array('contactInterval', 1, null, 1),
            array('contactInterval', 1, 2, 2),
            array('contactInterval', 2, 1, 1),
            array('downloadPeriodDelay', 1, null, 1),
            array('downloadPeriodDelay', 1, 2, 2),
            array('downloadPeriodDelay', 2, 1, 1),
            array('downloadCycleDelay', 1, null, 1),
            array('downloadCycleDelay', 1, 2, 2),
            array('downloadCycleDelay', 2, 1, 1),
            array('downloadFragmentDelay', 1, null, 1),
            array('downloadFragmentDelay', 1, 2, 2),
            array('downloadFragmentDelay', 2, 1, 1),
            array('downloadMaxPriority', 1, null, 1),
            array('downloadMaxPriority', 1, 2, 2),
            array('downloadMaxPriority', 2, 1, 1),
            array('downloadTimeout', 1, null, 1),
            array('downloadTimeout', 1, 2, 2),
            array('downloadTimeout', 2, 1, 1),
            array('packageDeployment', 0, 0, 0),
            array('packageDeployment', 0, null, 0),
            array('packageDeployment', 1, 0, 0),
            array('packageDeployment', 1, null, 1),
            array('allowScan', 0, 0, 0),
            array('allowScan', 0, null, 0),
            array('allowScan', 1, 0, 0),
            array('allowScan', 1, null, 1),
            array('scanSnmp', 0, 0, 0),
            array('scanSnmp', 0, null, 0),
            array('scanSnmp', 1, 0, 0),
            array('scanSnmp', 1, null, 1),
        );
    }

    /**
     * @dataProvider getEffectiveConfigProvider
     */
    public function testGetEffectiveConfig($option, $defaultValue, $clientValue, $expectedValue)
    {
        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getDefaultConfig', 'getConfig']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getDefaultConfig')->with($option)->willReturn($defaultValue);
        $model->method('getConfig')->with($option)->willReturn($clientValue);

        $this->assertSame($expectedValue, $model->getEffectiveConfig($option));
    }

    public function getEffectiveConfigForInventoryIntervalProvider()
    {
        return array(
            array(-1, array(1), 1, -1), // global value -1 always precedes
            array( 0, array(-1), -1, 0), // global value 0 always precedes
            array(1, array(2, null), 3, 2), // smallest value from groups/client
            array(1, array(3, null), 2, 2), // smallest value from groups/client
            array(1, array(), null, 1), // no values defined, fall back to global value
            array(1, array(), 2, 2), // smallest value from groups/client
            array(1, array(2, 3), null, 2), // no client value, use smallest group value
            array(1, array(0), -1, -1), // client value overrides default
            array(1, array(-1), 0, -1), // client value does not override default
        );
    }

    /**
     * @dataProvider getEffectiveConfigForInventoryIntervalProvider
     */
    public function testGetEffectiveConfigForInventoryInterval(
        $globalValue,
        $groupValues,
        $clientValue,
        $expectedValue
    ) {
        $config = $this->createMock('Model\Config');
        $config->method('__get')->with('inventoryInterval')->willReturn($globalValue);

        $groups = array();
        foreach ($groupValues as $groupValue) {
            $group = $this->createMock('Model\Group\Group');
            $group->method('getConfig')->with('inventoryInterval')->willReturn($groupValue);
            $groups[] = $group;
        }

        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->method('get')->with('Model\Config')->willReturn($config);

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getConfig', 'getGroups']);
        $model->setServiceLocator($serviceManager);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getConfig')->with('inventoryInterval')->willReturn($clientValue);
        $model->method('getGroups')->willReturn($groups);

        $this->assertSame($expectedValue, $model->getEffectiveConfig('inventoryInterval'));
    }

    public function testGetEffectiveConfigCache()
    {
        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getConfig']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->expects($this->exactly(2))
              ->method('getConfig')
              ->withConsecutive(array('option1'), array('option2'))
              ->willReturnOnConsecutiveCalls('value1', 'value2');

        $this->assertEquals('value1', $model->getEffectiveConfig('option1'));
        $this->assertEquals('value1', $model->getEffectiveConfig('option1')); // from cache
        $this->assertEquals('value2', $model->getEffectiveConfig('option2')); // non-cached value
    }

    public function getPackageAssignmentsProvider()
    {
        $package1 = [
            'packageName' => 'package1',
            'status' => Assignment::SUCCESS,
            'timestamp' => new DateTime('2014-12-30 19:02:23'),
        ];
        $package2 = [
            'packageName' => 'package2',
            'status' => Assignment::RUNNING,
            'timestamp' => new DateTime('2014-12-30 19:01:23'),
        ];
        // Non-default order. Default order tested separately.
        return [
            ['packageName', 'desc', $package2, $package1],
            ['status', 'asc', $package2, $package1],
            ['status', 'desc', $package1, $package2],
        ];
    }

    /**
     * @dataProvider getPackageAssignmentsProvider
     */
    public function testGetPackageAssignments($order, $direction, $package0, $package1)
    {
        $model = $this->getModel();
        $model->id = 1;

        $assignments = $model->getPackageAssignments($order, $direction);
        $this->assertInstanceOf(AbstractResultSet::class, $assignments);
        /** @var Assignment[] */
        $assignments = iterator_to_array($assignments);
        $this->assertCount(2, $assignments);
        $this->assertContainsOnlyInstancesOf(Assignment::class, $assignments);
        $this->assertEquals($package0, get_object_vars($assignments[0]));
        $this->assertEquals($package1, get_object_vars($assignments[1]));
    }

    public function testGetPackageAssignmentsDefaultOrder()
    {
        $model = $this->getModel();
        $model->id = 1;

        $assignments = $model->getPackageAssignments();
        $this->assertInstanceOf(AbstractResultSet::class, $assignments);
        /** @var Assignment[] */
        $assignments = iterator_to_array($assignments);
        $this->assertCount(2, $assignments);
        $this->assertContainsOnlyInstancesOf(Assignment::class, $assignments);
        $this->assertEquals(
            [
                'packageName' => 'package1',
                'status' => Assignment::SUCCESS,
                'timestamp' => new DateTime('2014-12-30 19:02:23'),
            ],
            get_object_vars($assignments[0])
        );
        $this->assertEquals(
            [
                'packageName' => 'package2',
                'status' => Assignment::RUNNING,
                'timestamp' => new DateTime('2014-12-30 19:01:23'),
            ],
            get_object_vars($assignments[1])
        );
    }

    public function testGetDownloadedPackageIds()
    {
        $model = $this->getModel();
        $model->id = 1;
        $this->assertEquals(array(1, 2), $model->getDownloadedPackageIds());
    }

    public function resetPackageProvider()
    {
        return array(
            array(1, 'resetPackageNotResetYet'),
            array(2, 'resetPackageAlreadyReset'),
        );
    }

    /**
     * @dataProvider resetPackageProvider
     */
    public function testResetPackage($packageId, $dataset)
    {
        $package = array('Id' => $packageId);

        $packageManager = $this->createMock('Model\Package\PackageManager');
        $packageManager->method('getPackage')->with('packageName')->willReturn($package);

        $model = $this->getModel(
            array(
                'Library\Now' => new \DateTime('2017-05-27T19:39:25'),
                'Model\Package\PackageManager' => $packageManager,
            )
        );
        $model->Id = 1;

        $model->resetPackage('packageName');

        $this->assertTablesEqual(
            $this->loadDataSet($dataset)->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function testResetPackageNotAssigned()
    {
        $package = array('Id' => 3);

        $packageManager = $this->createMock('Model\Package\PackageManager');
        $packageManager->method('getPackage')->with('packageName')->willReturn($package);

        $model = $this->getModel(array('Model\Package\PackageManager' => $packageManager));
        $model->Id = 1;

        try {
            $model->resetPackage('packageName');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Package "packageName" is not assigned to client 1', $e->getMessage());
        }

        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function testResetPackageCommit()
    {
        $package = array('Id' => 1);

        $packageManager = $this->createMock('Model\Package\PackageManager');
        $packageManager->method('getPackage')->with('packageName')->willReturn($package);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $resultSet = $this->createMock('Laminas\Db\ResultSet\AbstractResultSet');
        $resultSet->method('current')->willReturn(array('num' => 1));

        $select = $this->createMock('Laminas\Db\Sql\Select');

        $sql = $this->createMock('Laminas\Db\Sql\Sql');
        $sql->method('select')->willReturn($select);

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->method('getSql')->willReturn($sql);
        $clientConfig->method('selectWith')->willReturn($resultSet);
        $clientConfig->method('getAdapter')->willReturn($adapter);

        $model = $this->getModel(
            array(
                'Database\Table\ClientConfig' => $clientConfig,
                'Model\Package\PackageManager' => $packageManager,
            )
        );
        $model->Id = 1;

        $model->resetPackage('packageName');
    }

    public function testResetPackageRollbackOnException()
    {
        $package = array('Id' => 1);

        $packageManager = $this->createMock('Model\Package\PackageManager');
        $packageManager->method('getPackage')->with('packageName')->willReturn($package);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->method('getAdapter')->willReturn($adapter);
        $clientConfig->method('getSql')->willThrowException(new \RuntimeException('test message'));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $model = $this->getModel([
                'Database\Table\ClientConfig' => $clientConfig,
                'Model\Package\PackageManager' => $packageManager,
        ]);
        $model->Id = 1;

        $model->resetPackage('packageName');
    }

    public function testGetItemsDefaultArgs()
    {
        $result = $this->createStub(AbstractResultSet::class);

        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())
                    ->method('getItems')
                    ->with('type', array('Client' => 42), null, null)
                    ->willReturn($result);

        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->createPartialMock(Client::class, ['offsetGet']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setServiceLocator($serviceManager);

        $this->assertSame($result, $model->getItems('type'));
    }

    public function testGetItemsCustomArgs()
    {
        $result = $this->createStub(AbstractResultSet::class);

        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())
                    ->method('getItems')
                    ->with('type', array('filter' => 'arg', 'Client' => 42), 'order', 'direction')
                    ->willReturn($result);

        /** @var MockObject|ServiceLocatorInterface */
        $serviceManager = $this->createMock(ServiceLocatorInterface::class);
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->createPartialMock(Client::class, ['offsetGet']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setServiceLocator($serviceManager);

        $this->assertSame($result, $model->getItems('type', 'order', 'direction', array('filter' => 'arg')));
    }

    public function setGroupMembershipsNoActionProvider()
    {
        return array(
            array(
                array(),
                array(),
            ),
            array(
                array(),
                array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
            ),
            array(
                array(2 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
            ),
            array(
                array(1 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
            ),
            array(
                array(1 => \Model\Client\Client::MEMBERSHIP_ALWAYS),
                array('group1' => \Model\Client\Client::MEMBERSHIP_ALWAYS),
            ),
            array(
                array(1 => \Model\Client\Client::MEMBERSHIP_NEVER),
                array('group1' => \Model\Client\Client::MEMBERSHIP_NEVER),
            ),
            array(
                array(),
                array('ignore' => \Model\Client\Client::MEMBERSHIP_ALWAYS),
            ),
        );
    }

    /**
     * @dataProvider setGroupMembershipsNoActionProvider
     */
    public function testSetGroupMembershipsNoAction($oldMemberships, $newMemberships)
    {
        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->never())->method('insert');
        $groupMemberships->expects($this->never())->method('update');
        $groupMemberships->expects($this->never())->method('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
            )
        );

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships', '__destruct']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn($oldMemberships);
        $model->setServiceLocator($serviceManager);

        $cache = new \ReflectionProperty($model, '_groups');
        $cache->setAccessible(true);
        $cache->setValue($model, 'cache');

        $model->setGroupMemberships($newMemberships);
        $this->assertEquals('cache', $cache->getValue($model));
    }

    public function setGroupMembershipsInsertProvider()
    {
        return array(
            array(
                array(),
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
            array(
                array(),
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
            array(
                array(2 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
            array(
                array(2 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
        );
    }

    /**
     * @dataProvider setGroupMembershipsInsertProvider
     */
    public function testSetGroupMembershipsInsert($oldMemberships, $newMembership)
    {
        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->once())->method('insert')->with(
            array(
                'hardware_id' => 42,
                'group_id' => 1,
                'static' => $newMembership,
            )
        );
        $groupMemberships->expects($this->never())->method('update');
        $groupMemberships->expects($this->never())->method('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
            )
        );

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships', '__destruct']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn($oldMemberships);
        $model->setServiceLocator($serviceManager);

        $cache = new \ReflectionProperty($model, '_groups');
        $cache->setAccessible(true);
        $cache->setValue($model, 'cache');

        $model->setGroupMemberships(array('group1' => $newMembership));
        $this->assertNull($cache->getValue($model));
    }

    public function setGroupMembershipsUpdateProvider()
    {
        return array(
            array(
                \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_NEVER,
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
        );
    }

    /**
     * @dataProvider setGroupMembershipsUpdateProvider
     */
    public function testSetGroupMembershipsUpdate($oldMembership, $newMembership)
    {
        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->never())->method('insert');
        $groupMemberships->expects($this->once())->method('update')->with(
            array('static' => $newMembership),
            array(
             'hardware_id' => 42,
             'group_id' => 1,
            )
        );
        $groupMemberships->expects($this->never())->method('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
            )
        );

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships', '__destruct']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn(array(1 => $oldMembership));
        $model->setServiceLocator($serviceManager);

        $cache = new \ReflectionProperty($model, '_groups');
        $cache->setAccessible(true);
        $cache->setValue($model, 'cache');

        $model->setGroupMemberships(array('group1' => $newMembership));
        $this->assertNull($cache->getValue($model));
    }

    public function setGroupMembershipsDeleteProvider()
    {
        return array(
            array(\Model\Client\Client::MEMBERSHIP_ALWAYS),
            array(\Model\Client\Client::MEMBERSHIP_NEVER),
        );
    }

    /**
     * @dataProvider setGroupMembershipsDeleteProvider
     */
    public function testSetGroupMembershipsDelete($oldMembership)
    {
        $group1 = $this->createMock('Model\Group\Group');
        $group1->method('offsetGet')->willReturnMap(
            array(array('Id', 1), array('Name', 'group1'))
        );
        $group1->expects($this->once())->method('update')->with(true);

        $group2 = $this->createMock('Model\Group\Group');
        $group2->method('offsetGet')->willReturnMap(
            array(array('Id', 2), array('Name', 'group2'))
        );
        $group2->expects($this->never())->method('update');

        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->never())->method('insert');
        $groupMemberships->expects($this->never())->method('update');
        $groupMemberships->expects($this->once())->method('delete')->with(
            array(
                'hardware_id' => 42,
                'group_id' => 1,
            )
        );

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(array($group1, $group2));

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships', '__destruct']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn([1 => $oldMembership]);
        $model->setServiceLocator($serviceManager);

        $cache = new \ReflectionProperty($model, '_groups');
        $cache->setAccessible(true);
        $cache->setValue($model, 'cache');

        $model->setGroupMemberships(array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC));
        $this->assertNull($cache->getValue($model));
    }

    public function testSetGroupMembershipsMixedKeys()
    {
        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->exactly(2))->method('insert')->withConsecutive(
            array(
                array(
                    'hardware_id' => 42,
                    'group_id' => 1,
                    'static' => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                )
            ),
            array(
                array(
                    'hardware_id' => 42,
                    'group_id' => 3,
                    'static' => \Model\Client\Client::MEMBERSHIP_NEVER,
                )
            )
        );
        $groupMemberships->expects($this->never())->method('update');
        $groupMemberships->expects($this->never())->method('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
                array('Id' => 3, 'Name' => 'group3'),
            )
        );

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships', '__destruct']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn(array(2 => \Model\Client\Client::MEMBERSHIP_ALWAYS));
        $model->setServiceLocator($serviceManager);

        $model->setGroupMemberships(
            array(
                1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                'group2' => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                'group3' => \Model\Client\Client::MEMBERSHIP_NEVER,
            )
        );
    }

    public function testSetGroupMembershipsInvalidMembership()
    {
        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(array('Id' => 1, 'Name' => 'group1'))
        );

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(array('Model\Group\GroupManager', $groupManager))
                       );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships', '__destruct']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn(array());
        $model->setServiceLocator($serviceManager);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid membership type: 23');
        $model->setGroupMemberships(array('group1' => 23));
    }

    public function getGroupMembershipsProvider()
    {
        return array(
            array(
                \Model\Client\Client::MEMBERSHIP_ANY,
                array(
                    1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                    2 => \Model\Client\Client::MEMBERSHIP_NEVER,
                    3 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                )
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_MANUAL,
                array(
                    1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                    2 => \Model\Client\Client::MEMBERSHIP_NEVER,
                )
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
                array(1 => \Model\Client\Client::MEMBERSHIP_ALWAYS)
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_NEVER,
                array(2 => \Model\Client\Client::MEMBERSHIP_NEVER)
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                array(3 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC)
            ),
        );
    }

    /**
     * @dataProvider getGroupMembershipsProvider
     */
    public function testGetGroupMemberships($type, $expected)
    {
        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->expects($this->once())->method('updateCache');

        $model = $this->getModel(array('Model\Group\GroupManager' => $groupManager));
        $model->Id = 1;

        $this->assertSame($expected, $model->getGroupMemberships($type));
    }

    public function testGetGroupMembershipsInvalidType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Bad value for membership: 42');

        $model = $this->getModel();
        $model->getGroupMemberships(42);
    }

    public function testGetGroups()
    {
        $groups = array('group1', 'group2');

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->expects($this->once())->method('getGroups')->with('Member', 42)->willReturn(
            new \ArrayIterator($groups)
        );

        $model = $this->getModel(array('Model\Group\GroupManager' => $groupManager));
        $model->Id = 42;

        $this->assertEquals($groups, $model->getGroups());
        $this->assertEquals($groups, $model->getGroups()); // cached result
    }

    public function testSetCustomFields()
    {
        $customFieldManager = $this->createMock(CustomFieldManager::class);
        $customFieldManager->expects($this->once())->method('write')->with(42, 'data');

        $model = $this->getModel(array('Model\Client\CustomFieldManager' => $customFieldManager));
        $model->Id = 42;

        $model->setCustomFields('data');
    }

    public function testToDomDocument()
    {
        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');

        $model = $this->getModel();
        $model->setServiceLocator($serviceManager);

        // DomDocument constructor must be preserved. Otherwise setting the
        // formatOutput property would have no effect for whatever reason.
        $inventoryRequest = $this->getMockBuilder(\Protocol\Message\InventoryRequest::class)
                                 ->setConstructorArgs(
                                     [$this->createStub(\Protocol\Message\InventoryRequest\Content::class)]
                                 )->getMock();
        $inventoryRequest->expects($this->once())->method('loadClient')->with($model);

        $serviceManager->method('get')->with('Protocol\Message\InventoryRequest')->willReturn($inventoryRequest);

        $document = $model->toDomDocument();
        $this->assertInstanceOf('Protocol\Message\InventoryRequest', $document);
        $this->assertTrue($document->formatOutput);
    }
}
