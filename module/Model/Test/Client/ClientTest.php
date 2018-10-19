<?php
/**
 * Tests for Model\Client\Client
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

class ClientTest extends \Model\Test\AbstractTest
{
    protected static $_tables = array(
        'ClientsAndGroups',
        'WindowsProductKeys',
        'WindowsInstallations',
        'DuplicateSerials',
        'DuplicateAssetTags',
        'ClientConfig',
        'Packages',
        'PackageHistory',
        'GroupMemberships',
    );

    public function testObjectProperties()
    {
        $model = $this->_getModel();
        $this->assertInstanceOf('ArrayAccess', $model);
        $this->assertTrue(method_exists($model, 'exchangeArray'));
    }

    public function testOffsetGetExistingProperty()
    {
        $model = new \Model\Client\Client(array('key' => 'value'));
        $this->assertEquals('value', $model['key']);
    }

    public function testOffsetGetWindowsNotNull()
    {
        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
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
        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
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
        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->once())->method('read')->with(2)->willReturn('custom_fields');

        $model = $this->_getModel(array('Model\Client\CustomFieldManager' => $customFieldManager));
        $model['Id'] = 2;
        $this->assertEquals('custom_fields', $model['CustomFields']);
        $this->assertEquals('custom_fields', $model['CustomFields']); // cached result
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
        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
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
        $model = $this->getMockBuilder('Model\Client\Client')->setMethods(array('getItems'))->getMock();
        $model->expects($this->once())->method('getItems')->with('type')->willReturn('items');
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->with('Model\Config')->willReturn($config);

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('offsetGet', 'getGroups', '__destruct'))
                      ->getMock();
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->expects($this->exactly(2))->method('get')->with('Model\Config')->willReturn($config);

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('offsetGet', 'getGroups', '__destruct'))
                      ->getMock();
        $model->expects($this->exactly(3))->method('offsetGet')->willReturn(42);
        $model->expects($this->exactly(2))->method('getGroups')->willReturn(array());
        $model->setServiceLocator($serviceManager);

        $this->assertEquals('value1', $model->getDefaultConfig('option1'));
        $this->assertEquals('value1', $model->getDefaultConfig('option1')); // from cache
        $this->assertEquals('value2', $model->getDefaultConfig('option2')); // non-cached value to test group cache
    }

    public function testGetAllConfig()
    {
        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'getConfig'))
                      ->getMock();
        $model->method('getConfig')->willReturnMap(
            array(
                array('contactInterval', 2),
                array('inventoryInterval', 3),
                array('packageDeployment', 0),
                array('downloadPeriodDelay', 4),
                array('downloadCycleDelay', 5),
                array('downloadFragmentDelay', 6),
                array('downloadMaxPriority', 7),
                array('downloadTimeout', 8),
                array('allowScan', 1),
                array('scanSnmp', 0),
                array('scanThisNetwork', '192.0.2.0'),
            )
        );
        $this->assertSame(
            array(
                'Agent' => array(
                    'contactInterval' => 2,
                    'inventoryInterval' => 3,
                ),
                'Download' => array(
                    'packageDeployment' => 0,
                    'downloadPeriodDelay' => 4,
                    'downloadCycleDelay' => 5,
                    'downloadFragmentDelay' => 6,
                    'downloadMaxPriority' => 7,
                    'downloadTimeout' => 8,
                ),
                'Scan' => array(
                    'allowScan' => 0,
                    'scanSnmp' => 0,
                    'scanThisNetwork' => '192.0.2.0',
                ),
            ),
            $model->getAllConfig()
        );
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
        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('offsetGet', 'getDefaultConfig', 'getConfig'))
                      ->getMock();
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->with('Model\Config')->willReturn($config);

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('offsetGet', 'getConfig', 'getGroups'))
                      ->getMock();
        $model->setServiceLocator($serviceManager);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getConfig')->with('inventoryInterval')->willReturn($clientValue);
        $model->method('getGroups')->willReturn($groups);

        $this->assertSame($expectedValue, $model->getEffectiveConfig('inventoryInterval'));
    }

    public function testGetEffectiveConfigCache()
    {
        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('offsetGet', 'getConfig'))
                      ->getMock();
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
        $package1 = array(
            'PackageName' => 'package1',
            'Status' => \Model\Package\Assignment::SUCCESS,
            'Timestamp' => new \DateTime('2014-12-30 19:02:23'),
        );
        $package2 = array(
            'PackageName' => 'package2',
            'Status' => \Model\Package\Assignment::RUNNING,
            'Timestamp' => new \DateTime('2014-12-30 19:01:23'),
        );
        // Non-default order. Default order tested separately.
        return array(
            array('PackageName', 'desc', $package2, $package1),
            array('Status', 'asc', $package2, $package1),
            array('Status', 'desc', $package1, $package2),
        );
    }

    /**
     * @dataProvider getPackageAssignmentsProvider
     */
    public function testGetPackageAssignments($order, $direction, $package0, $package1)
    {
        $model = $this->_getModel();
        $model['Id'] = 1;

        $assignments = $model->getPackageAssignments($order, $direction);
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $assignments);
        $assignments = iterator_to_array($assignments);
        $this->assertCount(2, $assignments);
        $this->assertContainsOnlyInstancesOf('Model\Package\Assignment', $assignments);
        $this->assertEquals($package0, $assignments[0]->getArrayCopy());
        $this->assertEquals($package1, $assignments[1]->getArrayCopy());
    }

    public function testGetPackageAssignmentsDefaultOrder()
    {
        $model = $this->_getModel();
        $model['Id'] = 1;

        $assignments = $model->getPackageAssignments();
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $assignments);
        $assignments = iterator_to_array($assignments);
        $this->assertCount(2, $assignments);
        $this->assertContainsOnlyInstancesOf('Model\Package\Assignment', $assignments);
        $this->assertEquals(
            array(
                'PackageName' => 'package1',
                'Status' => \Model\Package\Assignment::SUCCESS,
                'Timestamp' => new \DateTime('2014-12-30 19:02:23'),
            ),
            $assignments[0]->getArrayCopy()
        );
        $this->assertEquals(
            array(
                'PackageName' => 'package2',
                'Status' => \Model\Package\Assignment::RUNNING,
                'Timestamp' => new \DateTime('2014-12-30 19:01:23'),
            ),
            $assignments[1]->getArrayCopy()
        );
    }

    public function testGetDownloadedPackageIds()
    {
        $model = $this->_getModel();
        $model['Id'] = 1;
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

        $model = $this->_getModel(
            array(
                'Library\Now' => new \DateTime('2017-05-27T19:39:25'),
                'Model\Package\PackageManager' => $packageManager,
            )
        );
        $model['Id'] = 1;

        $model->resetPackage('packageName');

        $this->assertTablesEqual(
            $this->_loadDataSet($dataset)->getTable('devices'),
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

        $model = $this->_getModel(array('Model\Package\PackageManager' => $packageManager));
        $model['Id'] = 1;

        try {
            $model->resetPackage('packageName');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Package "packageName" is not assigned to client 1', $e->getMessage());
        }

        $this->assertTablesEqual(
            $this->_loadDataSet()->getTable('devices'),
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

        $connection = $this->createMock('Zend\Db\Adapter\Driver\AbstractConnection');
        $connection->expects($this->at(0))->method('beginTransaction');
        $connection->expects($this->at(1))->method('commit');

        $driver = $this->createMock('Zend\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Zend\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $resultSet = $this->createMock('Zend\Db\ResultSet\AbstractResultSet');
        $resultSet->method('current')->willReturn(array('num' => 1));

        $select = $this->createMock('Zend\Db\Sql\Select');

        $sql = $this->createMock('Zend\Db\Sql\Sql');
        $sql->method('select')->willReturn($select);

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->method('getSql')->willReturn($sql);
        $clientConfig->method('selectWith')->willReturn($resultSet);
        $clientConfig->method('getAdapter')->willReturn($adapter);

        $model = $this->_getModel(
            array(
                'Database\Table\ClientConfig' => $clientConfig,
                'Model\Package\PackageManager' => $packageManager,
            )
        );
        $model['Id'] = 1;

        $model->resetPackage('packageName');
    }

    public function testResetPackageRollbackOnException()
    {
        $package = array('Id' => 1);

        $packageManager = $this->createMock('Model\Package\PackageManager');
        $packageManager->method('getPackage')->with('packageName')->willReturn($package);

        $connection = $this->createMock('Zend\Db\Adapter\Driver\AbstractConnection');
        $connection->expects($this->at(0))->method('beginTransaction');
        $connection->expects($this->at(1))->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Zend\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Zend\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $resultSet = $this->createMock('Zend\Db\ResultSet\AbstractResultSet');
        $resultSet->method('current')->willReturn(array('num' => 1));

        $select = $this->createMock('Zend\Db\Sql\Select');

        $sql = $this->createMock('Zend\Db\Sql\Sql');
        $sql->method('select')->willReturn($select);

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->at(0))->method('getSql')->willReturn($sql);
        $clientConfig->expects($this->at(1))->method('selectWith')->willReturn($resultSet);
        $clientConfig->expects($this->at(2))->method('getAdapter')->willReturn($adapter);
        $clientConfig->expects($this->at(3))->method('getSql')->willReturn($sql);
        $clientConfig->expects($this->at(4))->method('selectWith')->willThrowException(
            new \RuntimeException('test message')
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $model = $this->_getModel(
            array(
                'Database\Table\ClientConfig' => $clientConfig,
                'Model\Package\PackageManager' => $packageManager,
            )
        );
        $model['Id'] = 1;

        $model->resetPackage('packageName');
    }

    public function testGetItemsDefaultArgs()
    {
        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())
                    ->method('getItems')
                    ->with('type', array('Client' => 42), null, null)
                    ->willReturn('result');

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->getMockBuilder('Model\Client\Client')->setMethods(array('offsetGet'))->getMock();
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setServiceLocator($serviceManager);

        $this->assertEquals('result', $model->getItems('type'));
    }

    public function testGetItemsCustomArgs()
    {
        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())
                    ->method('getItems')
                    ->with('type', array('filter' => 'arg', 'Client' => 42), 'order', 'direction')
                    ->willReturn('result');

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->getMockBuilder('Model\Client\Client')->setMethods(array('offsetGet'))->getMock();
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setServiceLocator($serviceManager);

        $this->assertEquals('result', $model->getItems('type', 'order', 'direction', array('filter' => 'arg')));
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
        $groupMemberships = $this->createMock('Database\Table\GroupMemberships');
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('getGroupMemberships', '__destruct'))
                      ->setConstructorArgs(array(array('Id' => 42)))
                      ->getMock();
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
        $groupMemberships = $this->createMock('Database\Table\GroupMemberships');
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('getGroupMemberships', '__destruct'))
                      ->setConstructorArgs(array(array('Id' => 42)))
                      ->getMock();
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
        $groupMemberships = $this->createMock('Database\Table\GroupMemberships');
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('getGroupMemberships', '__destruct'))
                      ->setConstructorArgs(array(array('Id' => 42)))
                      ->getMock();
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

        $groupMemberships = $this->createMock('Database\Table\GroupMemberships');
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('getGroupMemberships', '__destruct'))
                      ->setConstructorArgs(array(array('Id' => 42)))
                      ->getMock();
        $model->method('getGroupMemberships')->willReturn(array(1 => $oldMembership));
        $model->setServiceLocator($serviceManager);

        $cache = new \ReflectionProperty($model, '_groups');
        $cache->setAccessible(true);
        $cache->setValue($model, 'cache');

        $model->setGroupMemberships(array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC));
        $this->assertNull($cache->getValue($model));
    }

    public function testSetGroupMembershipsMixedKeys()
    {
        $groupMemberships = $this->createMock('Database\Table\GroupMemberships');
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                               array('Database\Table\GroupMemberships', $groupMemberships),
                               array('Model\Group\GroupManager', $groupManager),
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('getGroupMemberships', '__destruct'))
                      ->setConstructorArgs(array(array('Id' => 42)))
                      ->getMock();
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

        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(array('Model\Group\GroupManager', $groupManager))
                       );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('getGroupMemberships', '__destruct'))
                      ->setConstructorArgs(array(array('Id' => 42)))
                      ->getMock();
        $model->method('getGroupMemberships')->willReturn(array());
        $model->setServiceLocator($serviceManager);

        $this->expectException('InvalidArgumentException', 'Invalid membership type: 23');
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
        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->expects($this->once())->method('updateCache');

        $model = $this->_getModel(array('Model\Group\GroupManager' => $groupManager));
        $model['Id'] = 1;

        $this->assertSame($expected, $model->getGroupMemberships($type));
    }

    public function testGetGroupMembershipsInvalidType()
    {
        $this->expectException('InvalidArgumentException', 'Bad value for membership: 42');

        $model = $this->_getModel();
        $model->getGroupMemberships(42);
    }

    public function testGetGroups()
    {
        $groups = array('group1', 'group2');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->expects($this->once())->method('getGroups')->with('Member', 42)->willReturn(
            new \ArrayIterator($groups)
        );

        $model = $this->_getModel(array('Model\Group\GroupManager' => $groupManager));
        $model['Id'] = 42;

        $this->assertEquals($groups, $model->getGroups());
        $this->assertEquals($groups, $model->getGroups()); // cached result
    }

    public function testSetCustomFields()
    {
        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->once())->method('write')->with(42, 'data');

        $model = $this->_getModel(array('Model\Client\CustomFieldManager' => $customFieldManager));
        $model['Id'] = 42;

        $model->setCustomFields('data');
    }

    public function testToDomDocument()
    {
        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');

        $model = $this->_getModel();
        $model->setServiceLocator($serviceManager);

        // DomDocument constructor must be preserved. Otherwise setting the
        // formatOutput property would have no effect for whatever reason.
        $inventoryRequest = $this->getMockBuilder('Protocol\Message\InventoryRequest')->getMock();
        $inventoryRequest->expects($this->exactly(2))->method('loadClient')->with(
            $model,
            $serviceManager
        );

        $serviceManager->method('get')->with('Protocol\Message\InventoryRequest')->willReturn($inventoryRequest);

        $document1 = $model->toDomDocument();
        $this->assertInstanceOf('Protocol\Message\InventoryRequest', $document1);
        $this->assertTrue($document1->formatOutput);

        $document2 = $model->toDomDocument();
        $this->assertInstanceOf('Protocol\Message\InventoryRequest', $document2);

        $this->assertNotSame($document1, $document2); // Test prototype cloning
    }
}
