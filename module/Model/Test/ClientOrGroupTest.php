<?php

/**
 * Tests for Model\ClientOrGroup
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

namespace Model\Test;

use Database\Table\ClientConfig;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Mockery;
use Model\ClientOrGroup;
use PHPUnit\Framework\MockObject\MockObject;

class ClientOrGroupTest extends AbstractTest
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** {@inheritdoc} */
    protected static $_tables = array('ClientConfig', 'Locks', 'PackageHistory', 'Packages');

    protected $_currentTimestamp;

    /** {@inheritdoc} */
    protected function loadDataSet($testName = null)
    {
        // Get current time from database as reference point for all operations.
        $this->_currentTimestamp = new \DateTime(
            $this->getConnection()->createQueryTable(
                'current',
                'SELECT CURRENT_TIMESTAMP AS current'
            )->getValue(0, 'current'),
            new \DateTimeZone('UTC')
        );

        $dataSet = parent::loadDataSet($testName);
        if (in_array('locks', $dataSet->getTableNames())) {
            // Replace offsets with timestamps (current - offset)
            $locks = $dataSet->getTable('locks');
            $count = $locks->getRowCount();
            $replacement = new \PHPUnit\DbUnit\DataSet\ReplacementDataSet($dataSet);
            for ($i = 0; $i < $count; $i++) {
                $offset = $locks->getValue($i, 'since');
                $interval = new \DateInterval(sprintf('PT%dS', trim($offset, '#')));
                $since = clone $this->_currentTimestamp;
                $since->sub($interval);
                $replacement->addFullReplacement($offset, $since->format('Y-m-d H:i:s'));
            }
            return $replacement;
        } else {
            return $dataSet;
        }
    }

    /**
     * Compose a ClientOrGroup mock with stubs for the given methods.
     */
    public function composeMock(array $mockedMethods = ['__destruct']): MockObject
    {
        return $this->getMockForAbstractClass(ClientOrGroup::class, [], '', false, true, true, $mockedMethods);
    }

    /**
     * Compare "locks" table with dataset using fuzzy timestamp match
     *
     * @param string $dataSetName Name of dataset file to compare
     */
    public function assertLocksTableEquals(?string $dataSetName)
    {
        $dataSetTable = $this->loadDataSet($dataSetName)->getTable('locks');
        $queryTable = $this->getConnection()->createQueryTable(
            'locks',
            'SELECT hardware_id, since FROM locks ORDER BY hardware_id'
        );
        $count = $dataSetTable->getRowCount();
        $this->assertEquals($count, $queryTable->getRowCount());
        for ($i = 0; $i < $count; $i++) {
            $dataSetRow = $dataSetTable->getRow($i);
            $queryRow = $queryTable->getRow($i);
            $dataSetDate = new \DateTime($dataSetRow['since']);
            $queryDate = new \DateTime($queryRow['since']);
            $this->assertThat($queryDate->getTimestamp(), $this->equalTo($dataSetDate->getTimestamp(), 1));
            $this->assertEquals($dataSetRow['hardware_id'], $queryRow['hardware_id']);
        }
    }

    /** {@inheritdoc} */
    public function testInterface()
    {
        $this->assertTrue(true); // Test does not apply to this class
    }

    public function testDestructor()
    {
        $model = $this->composeMock(['unlock']);
        $model->expects($this->once())->method('unlock');
        $model->__destruct();
    }

    public function testDestructorWithNestedLocks()
    {
        $config = $this->createMock('Model\Config');
        $config->method('__get')->with('lockValidity')->willReturn(42);

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', static::$serviceManager->get('Database\Nada')),
                array('Database\Table\Locks', static::$serviceManager->get('Database\Table\Locks')),
                array('Db', static::$serviceManager->get('Db')),
                array('Model\Config', $config),
            )
        );

        $model = $this->getMockBuilder($this->getClass())->getMockForAbstractClass();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 23;

        $model->lock();
        $model->lock();
        $model->__destruct();
        $this->assertLocksTableEquals(null);
    }

    public function lockWithDatabaseTimeProvider()
    {
        return array(
            array(42, 60, true, 'LockNew'),
            array(1, 58, true, 'LockReuse'),
            array(1, 62, false, null),
        );
    }

    /**
     * @dataProvider lockWithDatabaseTimeProvider
     */
    public function testLockWithDatabaseTime($id, $timeout, $success, $dataSetName)
    {
        $config = $this->createMock('Model\Config');
        $config->expects($this->once())->method('__get')->with('lockValidity')->willreturn($timeout);

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', static::$serviceManager->get('Database\Nada')),
                array('Database\Table\Locks', static::$serviceManager->get('Database\Table\Locks')),
                array('Db', static::$serviceManager->get('Db')),
                array('Model\Config', $config),
            )
        );

        $model = $this->composeMock();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = $id;

        $this->assertSame($success, $model->lock());
        $this->assertLocksTableEquals($dataSetName);
    }

    public function testLockRaceCondition()
    {
        $sql = $this->createMock('\Laminas\Db\Sql\Sql');
        $sql->method('select')->willReturn(new \Laminas\Db\Sql\Select());

        $locks = $this->createMock('Database\Table\Locks');
        $locks->method('getSql')->willReturn($sql);
        $locks->method('selectWith')->willReturn(new \ArrayIterator());
        $locks->method('insert')->will($this->throwException(new \RuntimeException('race condition')));

        $config = $this->createMock('Model\Config');

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', static::$serviceManager->get('Database\Nada')),
                array('Database\Table\Locks', $locks),
                array('Db', static::$serviceManager->get('Db')),
                array('Model\Config', $config),
            )
        );

        $model = $this->composeMock();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 42;

        $this->assertFalse($model->lock());
    }

    public function testUnlockWithoutLock()
    {
        $model = $this->composeMock(['__destruct', 'isLocked']);
        $model->expects($this->once())->method('isLocked')->willReturn(false);
        $model->unlock();
    }

    public function testUnlockWithReleasedLock()
    {
        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', static::$serviceManager->get('Database\Nada')),
                array('Database\Table\Locks', static::$serviceManager->get('Database\Table\Locks')),
                array('Db', static::$serviceManager->get('Db')),
            )
        );

        $model = $this->composeMock(['__destruct', 'isLocked']);
        $model->expects($this->once())->method('isLocked')->willReturn(true);
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 1;

        $current = clone $this->_currentTimestamp;
        $current->add(new \DateInterval('PT10S'));

        $expire = new \ReflectionProperty($model, '_lockTimeout');
        $expire->setAccessible(true);
        $expire->setValue($model, $current);

        $model->unlock();
        $this->assertLocksTableEquals('Unlock');
        $this->assertNull($expire->getValue($model));
    }

    public function testUnlockWithExpiredLock()
    {
        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', static::$serviceManager->get('Database\Nada')),
                array('Db', static::$serviceManager->get('Db')),
            )
        );

        $model = $this->composeMock(['__destruct', 'isLocked']);
        $model->expects($this->once())->method('isLocked')->willReturn(true);
        $model->setServiceLocator($serviceManager);

        $current = clone $this->_currentTimestamp;
        $current->sub(new \DateInterval('PT1S'));

        $expire = new \ReflectionProperty($model, '_lockTimeout');
        $expire->setAccessible(true);
        $expire->setValue($model, $current);

        try {
            $model->unlock();
            $this->fail('Expected exception was not thrown.');
        } catch (\PHPUnit\Framework\Error\Warning $e) {
            $this->assertEquals('Lock expired prematurely. Increase lock lifetime.', $e->getMessage());
        }
        $this->assertLocksTableEquals(null);
        $this->assertNull($expire->getValue($model));
    }

    public function testIsLockedFalse()
    {
        $model = $this->composeMock();
        $this->assertFalse($model->isLocked());
    }

    public function testIsLockedTrue()
    {
        $model = $this->composeMock();

        $expire = new \ReflectionProperty($model, '_lockNestCount');
        $expire->setAccessible(true);
        $expire->setValue($model, 2);

        $this->assertTrue($model->isLocked());
    }

    public function testNestedLocks()
    {
        $config = $this->createMock('Model\Config');
        $config->method('__get')->with('lockValidity')->willReturn(42);

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', static::$serviceManager->get('Database\Nada')),
                array('Database\Table\Locks', static::$serviceManager->get('Database\Table\Locks')),
                array('Db', static::$serviceManager->get('Db')),
                array('Model\Config', $config),
            )
        );

        $model = $this->composeMock();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 23;

        $this->assertTrue($model->lock());
        $this->assertTrue($model->lock());
        $model->unlock();
        $this->assertTrue($model->isLocked());
        $model->unlock();
        $this->assertFalse($model->isLocked());
    }

    public function testGetAssignablePackages()
    {
        $model = $this->composeMock();
        $model->setServiceLocator(static::$serviceManager);
        $model['Id'] = 1;
        $this->assertEquals(array('package1', 'package3'), $model->getAssignablePackages());
    }

    public function assignPackageProvider()
    {
        return array(
            array('package1', 1, 'AssignPackage'),
            array('package2', 2, null),
        );
    }

    /**
     * @dataProvider assignPackageProvider
     */
    public function testAssignPackage($name, $id, $dataSet)
    {
        $packageManager = $this->createMock('Model\Package\PackageManager');
        $packageManager->method('getPackage')
                       ->with($name)
                       ->willReturn(array('Id' => $id));

        $now = $this->createMock('DateTime');
        $now->method('format')->with('D M d H:i:s Y')->willReturn('current timestamp');

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array(
                    'Database\Table\ClientConfig',
                    static::$serviceManager->get('Database\Table\ClientConfig')
                ),
                array('Library\Now', $now),
                array('Model\Package\PackageManager', $packageManager),
            )
        );

        $model = $this->composeMock(['__destruct', 'getAssignablePackages']);
        $model->method('getAssignablePackages')->willReturn(array('package1', 'package3'));
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 1;
        $model->assignPackage($name);

        if ($dataSet) {
            $where = 'WHERE hardware_id < 10 ';
        } else {
            $where = '';
        }
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ' . $where .
                'ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function testRemovePackage()
    {
        $packageManager = $this->createMock('Model\Package\PackageManager');
        $packageManager->method('getPackage')
                       ->with('package5')
                       ->willReturn(array('Id' => 5));

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array(
                    'Database\Table\ClientConfig',
                    static::$serviceManager->get('Database\Table\ClientConfig')
                ),
                array('Model\Package\PackageManager', $packageManager),
            )
        );

        $model = $this->composeMock();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 1;
        $model->removePackage('package5');

        $this->assertTablesEqual(
            $this->loadDataSet('RemovePackage')->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ' .
                'WHERE hardware_id < 10 ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function getConfigProvider()
    {
        return array(
            array(10, 'packageDeployment', 0),
            array(11, 'packageDeployment', null),
            array(10, 'allowScan', 0),
            array(11, 'allowScan', null),
            array(10, 'scanThisNetwork', '192.0.2.0'),
            array(11, 'scanThisNetwork', null),
            array(10, 'scanSnmp', 0),
            array(11, 'scanSnmp', null),
            array(10, 'inventoryInterval', 23),
            array(11, 'inventoryInterval', null),
        );
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetConfig($id, $option, $value)
    {
        $config = $this->createMock('Model\Config');
        $config->method('getDbIdentifier')->with('inventoryInterval')->willReturn('FREQUENCY');

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array(
                    'Database\Table\ClientConfig',
                    static::$serviceManager->get('Database\Table\ClientConfig')
                ),
                array('Model\Config', $config),
            )
        );

        $model = $this->composeMock();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = $id;

        $this->assertSame($value, $model->getConfig($option));
    }

    public function testGetConfigCached()
    {
        $model = $this->composeMock();
        $model['Id'] = 42;

        $cache = new \ReflectionProperty($model, '_configCache');
        $cache->setAccessible(true);
        $cache->setValue($model, array('option' => 'value'));

        $this->assertEquals('value', $model->getConfig('option'));
    }

    public function setConfigProvider()
    {
        return array(
            array(10, 'inventoryInterval', 'FREQUENCY', null, null, null, 'SetConfigRegularDelete'),
            array(10, 'inventoryInterval', 'FREQUENCY', 42, 23, 42, 'SetConfigRegularUpdate'),
            array(10, 'contactInterval', 'PROLOG_FREQ', 42, null, 42, 'SetConfigRegularInsert'),
            array(10, 'packageDeployment', 'DOWNLOAD', 1, 0, null, 'SetConfigPackageDeploymentEnable'),
            array(10, 'scanSnmp', 'SNMP', 1, 0, null, 'SetConfigScanSnmpEnable'),
            array(10, 'allowScan', 'IPDISCOVER', 1, 0, null, 'SetConfigAllowScanEnable'),
            array(11, 'packageDeployment', 'DOWNLOAD', 0, null, 0, 'SetConfigPackageDeploymentDisable'),
            array(11, 'scanSnmp', 'SNMP', 0, null, 0, 'SetConfigScanSnmpDisable'),
            array(11, 'allowScan', 'IPDISCOVER', 0, null, 0, 'SetConfigAllowScanDisable'),
            array(11, 'scanThisNetwork', 'IPDISCOVER', 'addr', null, 'addr', 'SetConfigScanThisNetworkInsert'),
            array(10, 'scanThisNetwork', 'IPDISCOVER', null, 'addr', null, 'SetConfigScanThisNetworkDelete'),
        );
    }

    /**
     * @dataProvider setConfigProvider
     */
    public function testSetConfig($id, $option, $identifier, $value, $oldValue, $normalizedValue, $dataSet)
    {
        $config = $this->createMock('Model\Config');
        $config->method('getDbIdentifier')->with($option)->willReturn($identifier);

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Table\ClientConfig',
                    static::$serviceManager->get('Database\Table\ClientConfig')
                ),
                array('Model\Config', $config),
            )
        );

        $model = $this->composeMock(['__destruct', 'getConfig']);
        if ($normalizedValue === null) {
            $model->expects($this->never())->method('getConfig');
        } else {
            $model->expects($this->once())->method('getConfig')->with($option)->willReturn($oldValue);
        }
        $model->setServiceLocator($serviceManager);
        $model['Id'] = $id;

        $model->setConfig($option, $value);
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ' .
                'WHERE hardware_id >= 10 ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function testSetConfigUnchanged()
    {
        $config = $this->createMock('Model\Config');
        $config->method('getDbIdentifier')->with('inventoryInterval')->willReturn('FREQUENCY');

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->method('getAdapter')->willReturn(static::$serviceManager->get('Db'));
        $clientConfig->expects($this->never())->method('insert');
        $clientConfig->expects($this->never())->method('update');
        $clientConfig->expects($this->never())->method('delete');

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Table\ClientConfig', $clientConfig),
                array('Model\Config', $config),
            )
        );

        $model = $this->composeMock(['__destruct', 'getConfig']);
        $model->expects($this->once())->method('getConfig')->with('inventoryInterval')->willReturn(23);
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 10;

        $model->setConfig('inventoryInterval', '23');
    }

    public function testSetConfigRollbackOnException()
    {
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
        $clientConfig->method('delete')->willThrowException(new \RuntimeException('test message'));

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->with('Database\Table\ClientConfig')->willReturn($clientConfig);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $model = $this->composeMock(['offsetGet']);
        $model->method('offsetGet')->willReturn(1);
        $model->setServiceLocator($serviceManager);
        $model->setConfig('allowScan', null);
    }

    public function getAllConfigProvider()
    {
        return [
            [null, 0, 0, 1, 0, 0],
            [0, null, 0, 0, 1, 0],
            [0, 0, null, 0, 0, 1],
        ];
    }

    /**
     * @dataProvider getAllConfigProvider
     */
    public function testGetAllConfig(
        $packageDeployment,
        $allowScan,
        $scanSnmp,
        $expectedPackageDeployment,
        $expectedAllowScan,
        $expectedScanSnmp
    ) {
        $model = Mockery::mock(ClientOrGroup::class)->makePartial();
        $model->shouldReceive('getConfig')->with('contactInterval')->andReturn(1);
        $model->shouldReceive('getConfig')->with('inventoryInterval')->andReturn(2);
        $model->shouldReceive('getConfig')->with('downloadPeriodDelay')->andReturn(3);
        $model->shouldReceive('getConfig')->with('downloadCycleDelay')->andReturn(4);
        $model->shouldReceive('getConfig')->with('downloadFragmentDelay')->andReturn(5);
        $model->shouldReceive('getConfig')->with('downloadMaxPriority')->andReturn(6);
        $model->shouldReceive('getConfig')->with('downloadTimeout')->andReturn(7);
        // The following options can only be 0 or NULL
        $model->shouldReceive('getConfig')->with('packageDeployment')->andReturn($packageDeployment);
        $model->shouldReceive('getConfig')->with('allowScan')->andReturn($allowScan);
        $model->shouldReceive('getConfig')->with('scanSnmp')->andReturn($scanSnmp);

        $this->assertSame(
            [
                'Agent' => [
                    'contactInterval' => 1,
                    'inventoryInterval' => 2,
                ],
                'Download' => [
                    'packageDeployment' => $expectedPackageDeployment,
                    'downloadPeriodDelay' => 3,
                    'downloadCycleDelay' => 4,
                    'downloadFragmentDelay' => 5,
                    'downloadMaxPriority' => 6,
                    'downloadTimeout' => 7,
                ],
                'Scan' => [
                    'allowScan' => $expectedAllowScan,
                    'scanSnmp' => $expectedScanSnmp,
                ],
            ],
            $model->getAllConfig()
        );
    }

    public function testGetAllConfigNonCompactWithNullValues()
    {
        $model = Mockery::mock(ClientOrGroup::class)->makePartial();
        $model->shouldReceive('getConfig')->atLeast()->times(1)->andReturnNull();

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
                ],
            ],
            $model->getAllConfig()
        );
    }

    public function testGetExplicitConfigWithNonNullValues()
    {
        $model = Mockery::mock(ClientOrGroup::class)->makePartial();
        $model->shouldReceive('getConfig')->with('contactInterval')->andReturn(0);
        $model->shouldReceive('getConfig')->with('inventoryInterval')->andReturn(1);
        $model->shouldReceive('getConfig')->with('downloadPeriodDelay')->andReturn(2);
        $model->shouldReceive('getConfig')->with('downloadCycleDelay')->andReturn(3);
        $model->shouldReceive('getConfig')->with('downloadFragmentDelay')->andReturn(4);
        $model->shouldReceive('getConfig')->with('downloadMaxPriority')->andReturn(5);
        $model->shouldReceive('getConfig')->with('downloadTimeout')->andReturn(6);
        // The following options can only be 0 or NULL
        $model->shouldReceive('getConfig')->with('packageDeployment')->andReturn(0);
        $model->shouldReceive('getConfig')->with('allowScan')->andReturn(0);
        $model->shouldReceive('getConfig')->with('scanSnmp')->andReturn(0);

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
            ],
            $model->getExplicitConfig()
        );
    }

    public function testGetExplicitConfigWithNullValues()
    {
        $model = Mockery::mock(ClientOrGroup::class)->makePartial();
        $model->shouldReceive('getConfig')->atLeast()->times(1)->andReturnNull();

        $this->assertSame([], $model->getExplicitConfig());
    }
}
