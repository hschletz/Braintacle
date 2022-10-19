<?php

/**
 * Tests for Model\Group\Group
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

namespace Model\Test\Group;

use Database\Table\GroupMemberships;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform\AbstractPlatform;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Library\Random;
use Model\Client\ClientManager;
use Model\Group\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class GroupTest extends AbstractGroupTest
{
    /** {@inheritdoc} */
    protected static $_tables = array(
        'ClientConfig',
        'ClientsAndGroups',
        'ClientSystemInfo',
        'Clients',
        'GroupMemberships',
        'Packages',
    );

    public function getDefaultConfigProvider()
    {
        return array(
            array('allowScan', 0, 'scannersPerSubnet', 0),
            array('allowScan', 1, 'scannersPerSubnet', 2),
            array('foo', 'bar', 'foo', 'bar'),
        );
    }
    /**
     * @dataProvider getDefaultConfigProvider
     */
    public function testGetDefaultConfig($option, $expectedValue, $globalOptionName, $globalOptionValue)
    {
        $config = $this->createMock('Model\Config');
        $config->expects($this->once())->method('__get')->with($globalOptionName)->willReturn($globalOptionValue);
        $model = $this->getModel(array('Model\Config' => $config));
        $this->assertSame($expectedValue, $model->getDefaultConfig($option));
    }

    public function setMembersFromQueryProvider()
    {
        return array(
            array(\Model\Client\Client::MEMBERSHIP_ALWAYS, false, 'SetMembersFromQueryStatic'),
            array(\Model\Client\Client::MEMBERSHIP_NEVER, true, 'SetMembersFromQueryExcluded'),
        );
    }

    /**
     * @dataProvider setMembersFromQueryProvider
     */
    public function testSetMembersFromQuery($type, $simulateLockFailure, $dataSet)
    {
        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->once())
                      ->method('getClients')
                      ->with(
                          array('Id'),
                          null,
                          null,
                          'filter',
                          'search',
                          'operator',
                          'invert',
                          false,
                          true,
                          true
                      )->willReturn(array(array('Id' => 1), array('Id' => 2), array('Id' => 3), array('Id' => 5)));

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                                array('Model\Client\ClientManager', $clientManager),
                                array(
                                    'Database\Table\GroupMemberships',
                                    static::$serviceManager->get('Database\Table\GroupMemberships')
                                )
                           )
                       );

        $model = $this->createPartialMock(Group::class, ['lock', 'unlock']);
        if ($simulateLockFailure) {
            $model->expects($this->exactly(2))
                  ->method('lock')
                  ->will($this->onConsecutiveCalls(false, true));
        } else {
            $model->expects($this->once())->method('lock')->willReturn(true);
        }
        $model->expects($this->once())->method('unlock');
        $model['Id'] = 10;
        $model->setServiceLocator($serviceManager);

        $model->setMembersFromQuery($type, 'filter', 'search', 'operator', 'invert');
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('groups_cache'),
            $this->getConnection()->createQueryTable(
                'groups_cache',
                'SELECT hardware_id, group_id, static FROM groups_cache ORDER BY group_id, hardware_id'
            )
        );
    }

    public function testSetMembersFromQueryExceptionInTransaction()
    {
        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClients')->willReturn(array(array('Id' => 1)));

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);
        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $select = $this->createMock('Laminas\Db\Sql\Select');
        $select->method('columns')->will($this->returnSelf());
        $sql = $this->createMock('Laminas\Db\Sql\Sql');
        $sql->method('select')->willReturn($select);

        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->method('getAdapter')->willReturn($adapter);
        $groupMemberships->method('getSql')->willReturn($sql);
        $groupMemberships->method('selectWith')->willReturn(array());
        $groupMemberships->method('insert')->will($this->throwException(new \RuntimeException('test')));

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                                array('Model\Client\ClientManager', $clientManager),
                                array('Database\Table\GroupMemberships', $groupMemberships)
                           )
                       );

        $model = $this->createPartialMock(Group::class, ['lock', 'unlock']);
        $model->expects($this->once())->method('lock')->willReturn(true);
        $model->expects($this->once())->method('unlock');
        $model['Id'] = 10;
        $model->setServiceLocator($serviceManager);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test');
        $model->setMembersFromQuery(\Model\Client\Client::MEMBERSHIP_ALWAYS, 'filter', 'search', 'operator', 'invert');
    }

    public function setMembersFromQueryDynamicProvider()
    {
        return array(
            array(array()),
            array(array(array('columns' => array()))),
        );
    }

    /**
     * @dataProvider setMembersFromQueryDynamicProvider
     */
    public function testSetMembersFromQueryDynamic($joins)
    {
        /** @var MockObject|AbstractPlatform */
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getName')->willReturn('platform');

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getPlatform')->willReturn($platform);

        $select = $this->createMock('Laminas\Db\Sql\Select');
        $select->expects($this->exactly(2))
               ->method('getRawState')
               ->willReturnMap(
                   array(
                        array(\Laminas\Db\Sql\Select::COLUMNS, array('id')),
                        array(\Laminas\Db\Sql\Select::JOINS, $joins),
                   )
               );
        $select->method('getSqlString')->with($platform)->willReturn('query_new');

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->once())
                      ->method('getClients')
                      ->with(
                          array('Id'),
                          null,
                          null,
                          'filter',
                          'search',
                          'operator',
                          'invert',
                          false,
                          true,
                          false
                      )->willReturn($select);

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                                array('Db', $adapter),
                                array('Model\Client\ClientManager', $clientManager),
                                array('Database\Table\GroupInfo', $this->_groupInfo
                                )
                            )
                       );

        $model = $this->createPartialMock(Group::class, ['update']);
        $model->expects($this->once())
              ->method('update')
              ->with(true)
              ->willReturnCallback(function () use ($model) {
                  // Verify that value is set before update() gets called
                  $this->assertEquals('query_new', $model['DynamicMembersSql']);
              });
        $model['Id'] = 10;
        $model->setServiceLocator($serviceManager);

        $model->setMembersFromQuery(
            \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
            'filter',
            'search',
            'operator',
            'invert'
        );
        $this->assertTablesEqual(
            $this->loadDataSet('SetMembersFromQueryDynamic')->getTable('groups'),
            $this->getConnection()->createQueryTable(
                'groups',
                'SELECT hardware_id, request FROM groups ORDER BY hardware_id'
            )
        );
    }

    public function testSetMembersFromQueryDynamicInvalidQuery()
    {
        $joins = array(
            array('columns' => array()),
            array('columns' => array('name')),
        );
        $select = $this->createMock('Laminas\Db\Sql\Select');
        $select->expects($this->exactly(2))
               ->method('getRawState')
               ->will(
                   $this->returnValueMap(
                       array(
                           array(\Laminas\Db\Sql\Select::COLUMNS, array('id')),
                           array(\Laminas\Db\Sql\Select::JOINS, $joins),
                       )
                   )
               );
        $select->expects($this->never())->method('getSqlString');

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClients')->willReturn($select);

        $model = $this->getModel(array('Model\Client\ClientManager' => $clientManager));
        $model->id = 10;

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Expected 1 column, got 2');
        $model->setMembersFromQuery(
            \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
            'filter',
            'search',
            'operator',
            'invert'
        );
    }

    public function updateProvider()
    {
        return array(
            array(true, false, null, true, null), // force update, but no query
            array(true, true, null, false, null), // force update, but locking fails
            array(false, true, new \DateTime('2015-07-23 20:21:00'), true, null), // not expired yet
            array(true, true, new \DateTime('2015-07-23 20:21:00'), true, 'Update'), // not expired, but forced
            array(false, true, new \DateTime('2015-07-23 20:19:00'), true, 'Update'), // expired
            array(false, true, null, true, 'Update'), // no cache yet
        );
    }
    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($force, $setSql, $expires, $lockSuccess, $dataSet)
    {
        $now = new \DateTime('2015-07-23 20:20:00');

        $random = $this->createMock(Random::class);
        $random->method('getInteger')->willReturn(42);

        $config = $this->createMock('Model\Config');
        $config->method('__get')->will(
            $this->returnValueMap(
                array(
                    array('groupCacheExpirationInterval', 600),
                    array('groupCacheExpirationFuzz', 60),
                )
            )
        );

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')
                       ->willReturnMap(
                           array(
                                array(
                                    'Database\Table\Clients',
                                    static::$serviceManager->get('Database\Table\Clients')
                                ),
                                array('Database\Table\GroupInfo', $this->_groupInfo),
                                array(
                                    'Database\Table\GroupMemberships',
                                    static::$serviceManager->get('Database\Table\GroupMemberships')
                                ),
                                array('Library\Now', $now),
                                array('Library\Random', $random),
                                array('Model\Config', $config),
                           )
                       );

        $model = $this->createPartialMock(Group::class, ['lock', 'unlock']);
        $model->method('lock')->willReturn($lockSuccess);
        if ($dataSet !== null) {
            $model->expects($this->once())->method('unlock');
        }
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 10;
        $model['DynamicMembersSql'] = $setSql ? 'SELECT id FROM hardware WHERE id IN(2,3,4,5)' : null;
        $model['CacheCreationDate'] = null;
        $model['CacheExpirationDate'] = $expires;

        $model->update($force);
        // CacheCreationDate is only updated when there was data to alter ($dataSet !== null)
        $this->assertEquals(
            ($dataSet === null) ? null : $now,
            $model['CacheCreationDate']
        );
        // CacheExpirationDate is either updated ($dataSet !== null) or kept at initialized value
        $this->assertEquals(
            ($dataSet === null) ? $expires : new \DateTime('2015-07-23 20:30:42'),
            $model['CacheExpirationDate']
        );
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('groups'),
            $this->getConnection()->createQueryTable(
                'groups',
                'SELECT hardware_id, request, create_time, revalidate_from FROM groups ORDER BY hardware_id'
            )
        );
    }

    public function testGetPackagesDefaultOrder()
    {
        $model = $this->getModel();
        $model->id = 10;
        $this->assertEquals(array('package1', 'package2'), $model->getPackages());
    }

    public function testGetPackagesReverseOrder()
    {
        $model = $this->getModel();
        $model->id = 10;
        $this->assertEquals(array('package2', 'package1'), $model->getPackages('desc'));
    }
}
