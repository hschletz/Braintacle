<?php

/**
 * Tests for Model\Group\GroupManager
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Group\Group;
use Braintacle\Locks;
use Database\Table\ClientConfig;
use Database\Table\ClientsAndGroups;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use DateTimeImmutable;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Mockery;
use Model\Group\GroupManager;
use Nada\Database\AbstractDatabase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

class GroupManagerTest extends AbstractGroupTestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public static function createGroupProvider()
    {
        return array(
            array('description', 'description'),
            array('', null),
        );
    }

    /**
     * @dataProvider createGroupProvider
     */
    public function testCreateGroup($description, $expectedDescription)
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2015-02-12 22:07:00'));

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [ClockInterface::class, $clock],
            [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
            [ClientsAndGroups::class, static::$serviceManager->get(ClientsAndGroups::class)],
            [GroupInfo::class, $this->_groupInfo],
        ]);

        $model = new GroupManager($serviceManager);
        $model->createGroup('name3', $description);

        $table = static::$serviceManager->get('Database\Table\ClientsAndGroups');
        $id = $table->select(array('name' => 'name3', 'deviceid' => '_SYSTEMGROUP_'))->current()['id'];
        $dataSet = new \PHPUnit\DbUnit\DataSet\ReplacementDataSet($this->loadDataSet('CreateGroup'));
        $dataSet->addFullReplacement('#ID#', $id);
        $dataSet->addFullReplacement('#DESCRIPTION#', $expectedDescription);
        $connection = $this->getConnection();
        $this->assertTablesEqual(
            $dataSet->getTable('hardware'),
            $connection->createQueryTable(
                'hardware',
                'SELECT id, deviceid, name, description, lastdate FROM hardware'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('groups'),
            $connection->createQueryTable(
                'groups',
                'SELECT hardware_id, request, create_time, revalidate_from FROM groups'
            )
        );
    }

    public function testCreateGroupEmptyName()
    {
        $model = $this->getModel();
        try {
            $model->createGroup('');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Group name is empty', $e->getMessage());
            $dataSet = $this->loadDataSet();
            $connection = $this->getConnection();
            $this->assertTablesEqual(
                $dataSet->getTable('hardware'),
                $connection->createQueryTable(
                    'hardware',
                    'SELECT id, deviceid, name, description, lastdate FROM hardware'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('groups'),
                $connection->createQueryTable(
                    'groups',
                    'SELECT hardware_id, request, create_time, revalidate_from FROM groups'
                )
            );
        }
    }

    public function testCreateGroupExists()
    {
        $model = $this->getModel();
        try {
            $model->createGroup('name2');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Group already exists: name2', $e->getMessage());
            $dataSet = $this->loadDataSet();
            $connection = $this->getConnection();
            $this->assertTablesEqual(
                $dataSet->getTable('hardware'),
                $connection->createQueryTable(
                    'hardware',
                    'SELECT id, deviceid, name, description, lastdate FROM hardware'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('groups'),
                $connection->createQueryTable(
                    'groups',
                    'SELECT hardware_id, request, create_time, revalidate_from FROM groups'
                )
            );
        }
    }

    public function testCreateGroupRollbackOnException()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $resultSet = $this->createMock('Laminas\Db\ResultSet\AbstractResultSet');
        $resultSet->method('count')->willReturn(0);

        $clientsAndGroups = $this->createMock('Database\Table\ClientsAndGroups');
        $clientsAndGroups->method('select')->willReturn($resultSet);
        $clientsAndGroups->method('insert')->willThrowException(new \RuntimeException('test message'));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable());

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [Adapter::class, $adapter],
            [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
            [ClockInterface::class, $clock],
            [ClientsAndGroups::class, $clientsAndGroups],
        ]);

        $model = new GroupManager($serviceManager);
        $model->createGroup('name', 'description');
    }

    public function testDeleteGroup()
    {
        $group = new Group();
        $group->id = 1;

        $locks = $this->createMock(Locks::class);
        $locks->method('lock')->with($group)->willReturn(true);
        $locks->expects($this->once())->method('release')->with($group);

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
            [ClientConfig::class, static::$serviceManager->get(ClientConfig::class)],
            [ClientsAndGroups::class, static::$serviceManager->get(ClientsAndGroups::class)],
            [GroupMemberships::class, static::$serviceManager->get(GroupMemberships::class)],
            [GroupInfo::class, $this->_groupInfo],
            [Locks::class, $locks],
        ]);

        $model = new GroupManager($serviceManager);
        $model->deleteGroup($group);

        $dataSet = $this->loadDataSet('DeleteGroup');
        $connection = $this->getConnection();
        $this->assertTablesEqual(
            $dataSet->getTable('hardware'),
            $connection->createQueryTable(
                'hardware',
                'SELECT id, deviceid, name, description, lastdate FROM hardware'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('groups'),
            $connection->createQueryTable(
                'groups',
                'SELECT hardware_id, request, create_time, revalidate_from FROM groups'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('groups_cache'),
            $connection->createQueryTable(
                'groups_cache',
                'SELECT hardware_id, group_id FROM groups_cache'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('devices'),
            $connection->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue FROM devices'
            )
        );
    }

    public function testDeleteGroupLocked()
    {
        $group = $this->createMock(Group::class);

        $locks = $this->createMock(Locks::class);
        $locks->method('lock')->with($group)->willReturn(false);

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->with(Locks::class)->willReturn($locks);

        $model = new GroupManager($serviceManager);
        try {
            $model->deleteGroup($group);
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Group\RuntimeException $e) {
            $this->assertEquals('Cannot delete group because it is locked', $e->getMessage());
            $dataSet = $this->loadDataSet();
            $connection = $this->getConnection();
            $this->assertTablesEqual(
                $dataSet->getTable('hardware'),
                $connection->createQueryTable(
                    'hardware',
                    'SELECT id, deviceid, name, description, lastdate FROM hardware'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('groups'),
                $connection->createQueryTable(
                    'groups',
                    'SELECT hardware_id, request, create_time, revalidate_from FROM groups'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('groups_cache'),
                $connection->createQueryTable(
                    'groups_cache',
                    'SELECT group_id, hardware_id, static FROM groups_cache ORDER BY group_id, hardware_id'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('devices'),
                $connection->createQueryTable(
                    'devices',
                    'SELECT hardware_id, name, ivalue FROM devices'
                )
            );
        }
    }

    public function testDeleteGroupDatabaseError()
    {
        $group = new Group();
        $group->id = 1;

        $clientsAndGroups = $this->createMock(ClientsAndGroups::class);
        $clientsAndGroups->method('delete')->will($this->throwException(new \RuntimeException('database error')));

        $locks = $this->createMock(Locks::class);
        $locks->method('lock')->with($group)->willReturn(true);

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
            [ClientConfig::class, static::$serviceManager->get(ClientConfig::class)],
            [GroupMemberships::class, static::$serviceManager->get(GroupMemberships::class)],
            [ClientsAndGroups::class, $clientsAndGroups],
            [GroupInfo::class, $this->_groupInfo],
            [Locks::class, $locks],
        ]);

        $model = new GroupManager($serviceManager);
        try {
            $model->deleteGroup($group);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('database error', $e->getMessage());
            $dataSet = $this->loadDataSet();
            $connection = $this->getConnection();
            $this->assertTablesEqual(
                $dataSet->getTable('hardware'),
                $connection->createQueryTable(
                    'hardware',
                    'SELECT id, deviceid, name, description, lastdate FROM hardware'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('groups'),
                $connection->createQueryTable(
                    'groups',
                    'SELECT hardware_id, request, create_time, revalidate_from FROM groups'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('groups_cache'),
                $connection->createQueryTable(
                    'groups_cache',
                    'SELECT group_id, hardware_id, static FROM groups_cache ORDER BY group_id, hardware_id'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('devices'),
                $connection->createQueryTable(
                    'devices',
                    'SELECT hardware_id, name, ivalue FROM devices'
                )
            );
        }
    }
}
