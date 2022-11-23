<?php

/**
 * Tests for Model\Group\GroupManager
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

use Database\Table\ClientConfig;
use Database\Table\ClientsAndGroups;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use DateTime;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\ServiceManager\ServiceManager;
use Mockery;
use Model\Group\Group;
use Model\Group\GroupManager;
use PHPUnit\Framework\MockObject\MockObject;

class GroupManagerTest extends AbstractGroupTest
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** {@inheritdoc} */
    protected static $_tables = array('ClientConfig', 'ClientsAndGroups', 'GroupMemberships');

    public function getGroupsProvider()
    {
        $group1 = array(
            'Id' => '1',
            'Name' => 'name1',
            'CreationDate' => new \DateTime('2015-02-02 19:01:00'),
            'Description' => 'description1',
            'DynamicMembersSql' => 'request1',
            'CacheExpirationDate' => new \DateTime('2015-02-08 19:35:30'),
            'CacheCreationDate' => new \DateTime('2015-02-04 20:46:23'),
        );
        $group2 = array(
            'Id' => '2',
            'Name' => 'name2',
            'CreationDate' => new \DateTime('2015-02-02 19:02:00'),
            'Description' => null,
            'DynamicMembersSql' => 'request2',
            'CacheExpirationDate' => new \DateTime('2015-02-08 19:36:30'),
            'CacheCreationDate' => new \DateTime('2015-02-04 20:46:24'),
        );
        return array(
            array(null, null, 'Name', 'desc', array($group2, $group1), 'never'),
            array('Id', '2', null, null, array($group2), 'never'),
            array('Name', 'name1', null, null, array($group1), 'never'),
            array('Expired', null, null, null, array($group1), 'never'),
            array('Member', '3', 'Name', 'asc', array($group1, $group2), 'once'),
            array('Member', '4', null, null, array($group1), 'once'),
        );
    }

    /**
     * @dataProvider getGroupsProvider
     */
    public function testGetGroups($filter, $filterArg, $order, $direction, $expected, $updateCache)
    {
        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Table\GroupInfo', $this->_groupInfo),
                array('Library\Now', new \DateTime('2015-02-08 19:36:29')),
                array('Model\Config', $this->_config),
            )
        );

        $model = Mockery::mock(GroupManager::class, [$serviceManager])->makePartial();
        $model->shouldReceive('updateCache')->$updateCache();

        $resultSet = $model->getGroups($filter, $filterArg, $order, $direction);
        $this->assertInstanceOf('Laminas\Db\ResultSet\AbstractResultSet', $resultSet);
        $groups = iterator_to_array($resultSet);
        $this->assertContainsOnlyInstancesOf('Model\Group\Group', $groups);
        $this->assertCount(count($expected), $groups);
        foreach ($groups as $index => $group) {
            $this->assertEquals($expected[$index], $group->getArrayCopy());
        }
    }

    public function testGetGroupsInvalidFilter()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid group filter: invalid');

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->with(GroupInfo::class)->willReturn($this->_groupInfo);

        $model = new GroupManager($serviceManager);
        $resultSet = $model->getGroups('invalid');
    }

    public function testGetGroup()
    {
        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->with(GroupInfo::class)->willReturn($this->_groupInfo);

        $model = new GroupManager($serviceManager);
        $group = $model->getGroup('name2');
        $this->assertInstanceOf('Model\Group\Group', $group);
        $this->assertEquals('name2', $group['Name']);
    }

    public function testGetGroupNonExistentGroup()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Unknown group name: invalid');

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->with(GroupInfo::class)->willReturn($this->_groupInfo);

        $model = new GroupManager($serviceManager);
        $group = $model->getGroup('invalid');
    }

    public function testGetGroupNoName()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('No group name given');
        $model = $this->getModel();
        $group = $model->getGroup('');
    }

    public function createGroupProvider()
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
        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            ['Library\Now', new DateTime('2015-02-12 22:07:00')],
            ['Database\Nada', static::$serviceManager->get('Database\Nada')],
            ['Db', static::$serviceManager->get('Db')],
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

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            ['Db', $adapter],
            ['Database\Nada', static::$serviceManager->get('Database\Nada')],
            ['Library\Now', static::$serviceManager->get('Library\Now')],
            [ClientsAndGroups::class, $clientsAndGroups],
        ]);

        $model = new GroupManager($serviceManager);
        $model->createGroup('name', 'description');
    }

    public function testDeleteGroup()
    {
        /** @var MockObject|Group */
        $group = $this->createMock('Model\Group\Group');
        $group->method('lock')->willReturn(true);
        $group->method('offsetGet')->with('Id')->willReturn(1);
        $group->expects($this->once())->method('unlock');

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            [ClientConfig::class, static::$serviceManager->get(ClientConfig::class)],
            [ClientsAndGroups::class, static::$serviceManager->get(ClientsAndGroups::class)],
            [GroupMemberships::class, static::$serviceManager->get(GroupMemberships::class)],
            [GroupInfo::class, $this->_groupInfo],
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
        $group = $this->createMock('Model\Group\Group');
        $group->method('lock')->willReturn(false);

        $model = $this->getModel();
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
                    'SELECT group_id, hardware_id, static FROM groups_cache'
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
        /** @var MockObject|Group */
        $group = $this->createMock('Model\Group\Group');
        $group->method('lock')->willReturn(true);

        $clientsAndGroups = $this->createMock(ClientsAndGroups::class);
        $clientsAndGroups->method('delete')->will($this->throwException(new \RuntimeException('database error')));

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            [ClientConfig::class, static::$serviceManager->get(ClientConfig::class)],
            [GroupMemberships::class, static::$serviceManager->get(GroupMemberships::class)],
            [ClientsAndGroups::class, $clientsAndGroups],
            [GroupInfo::class, $this->_groupInfo],
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
                    'SELECT group_id, hardware_id, static FROM groups_cache'
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

    public function testUpdateCache()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->once())->method('update')->with(true);

        $model = $this->createPartialMock(GroupManager::class, ['getGroups']);
        $model->method('getGroups')->with('Expired')->willReturn([$group]);
        $model->updateCache();
    }
}
