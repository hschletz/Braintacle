<?php

/**
 * Tests for Model\Group\GroupManager
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

use ArrayIterator;
use Database\Table\ClientConfig;
use Database\Table\ClientsAndGroups;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use DateTime;
use Iterator;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Hydrator\AbstractHydrator;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Mockery;
use Model\Config;
use Model\Group\Group;
use Model\Group\GroupManager;
use Nada\Database\AbstractDatabase;
use RuntimeException;

class GroupManagerTest extends \Model\Test\AbstractTest
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** {@inheritdoc} */
    protected static $_tables = ['ClientConfig', 'ClientsAndGroups', 'GroupInfo', 'GroupMemberships'];

    public function getGroupsProvider()
    {
        $group1 = [
            'id' => '1',
            'name' => 'name1',
            'lastdate' => '2015-02-02 19:01:00',
            'description' => 'description1',
            'request' => 'request1',
            'revalidate_from' => 1423420500,
            'create_time' => 1423079183,
        ];
        $group2 = [
            'id' => '2',
            'name' => 'name2',
            'lastdate' => '2015-02-02 19:02:00',
            'description' => null,
            'request' => 'request2',
            'revalidate_from' => 1423420560,
            'create_time' => 1423079184,
        ];
        return [
            [null, null, 'name', 'desc', [$group2, $group1], 'never'],
            ['Id', '2', null, null, [$group2], 'never'],
            ['Name', 'name1', null, null, [$group1], 'never'],
            ['Expired', null, null, null, [$group1], 'never'],
            ['Member', '3', 'name', 'asc', [$group1, $group2], 'once'],
            ['Member', '4', null, null, [$group1], 'once'],
        ];
    }

    /**
     * @dataProvider getGroupsProvider
     */
    public function testGetGroups($filter, $filterArg, $order, $direction, $expected, $updateCache)
    {
        $hydrator = $this->createStub(AbstractHydrator::class);
        $hydrator->method('extractName')->with($order)->willReturnArgument(0);

        $iterator = $this->createStub(Iterator::class);

        $groupInfo = $this->createStub(GroupInfo::class);
        $groupInfo->method('getHydrator')->willReturn($hydrator);
        $groupInfo->method('getIterator')->with($this->callback(function ($data) use ($expected) {
            $this->assertEquals($expected, iterator_to_array($data));
            return true;
        }))->willReturn($iterator);

        $config = $this->createStub(Config::class);
        $config->method('__get')->with('groupCacheExpirationInterval')->willReturn(30);

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            ['Database\Table\GroupInfo', $groupInfo],
            ['Library\Now', new \DateTime('2015-02-08 19:36:29')],
            ['Model\Config', $config],
        ]);

        $model = Mockery::mock(GroupManager::class, [$serviceManager])->makePartial();
        $model->shouldReceive('updateCache')->$updateCache();

        $this->assertSame($iterator, $model->getGroups($filter, $filterArg, $order, $direction));
    }

    public function testGetGroupsInvalidFilter()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid group filter: invalid');

        $sql = $this->createStub(Sql::class);

        $adapter = $this->createStub(AdapterInterface::class);

        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')->with('Db')->willReturn($adapter);

        $model = new GroupManager($serviceManager);
        $model->getGroups('invalid');
    }

    public function testGetGroup()
    {
        $group = $this->createStub(Group::class);

        $resultSet = $this->createStub(AbstractResultSet::class);
        $resultSet->method('current')->willReturn($group);

        $model = $this->createPartialMock(GroupManager::class, ['getGroups']);
        $model->method('getGroups')->with('Name', 'group')->willReturn($resultSet);

        $this->assertSame($group, $model->getGroup('group'));
    }

    public function testGetGroupNonExistentGroup()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Unknown group name: invalid');

        $resultSet = $this->createStub(AbstractResultSet::class);
        $resultSet->method('current')->willReturn(null);

        $model = $this->createPartialMock(GroupManager::class, ['getGroups']);
        $model->method('getGroups')->with('Name', 'invalid')->willReturn($resultSet);

        $model->getGroup('invalid');
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
        $resultSetEmpty = $this->createStub(ResultSetInterface::class);
        $resultSetEmpty->method('count')->willReturn(0);

        $resultSetNonEmpty = $this->createStub(ResultSetInterface::class);
        $resultSetNonEmpty->method('current')->willReturn(['id' => 42]);

        $clientsAndGroups = $this->createMock(ClientsAndGroups::class);
        $clientsAndGroups->method('select')
                         ->with(['name' => 'newGroup', 'deviceid' => '_SYSTEMGROUP_'])
                         ->willReturnOnConsecutiveCalls($resultSetEmpty, $resultSetNonEmpty);
        $clientsAndGroups->expects($this->once())->method('insert')->with([
            'name' => 'newGroup',
            'description' => $expectedDescription,
            'deviceid' => '_SYSTEMGROUP_',
            'lastdate' => 'now_formatted',
        ]);

        $now = $this->createMock(DateTime::class);
        $now->method('format')->with('datetime_format')->willReturn('now_formatted');
        $now->method('getTimestamp')->willReturn('now_timestamp');

        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('beginTransaction')->once()->ordered();
        $connection->shouldReceive('commit')->ordered();
        $connection->shouldNotReceive('rollBack');

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);

        $nada = $this->createStub(AbstractDatabase::class);
        $nada->method('timestampFormatPhp')->willReturn('datetime_format');

        $groupInfo = $this->createMock(GroupInfo::class);
        $groupInfo->expects($this->once())->method('insert')->with([
            'hardware_id' => 42,
            'create_time' => 'now_timestamp',
        ]);

        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [ClientsAndGroups::class, $clientsAndGroups],
            ['Library\Now', $now],
            ['Db', $adapter],
            ['Database\Nada', $nada],
            [GroupInfo::class, $groupInfo],
        ]);

        $model = new GroupManager($serviceManager);
        $model->createGroup('newGroup', $description);
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
        $connection = $this->createMock('Laminas\Db\Adapter\Driver\AbstractConnection');
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

        $model = $this->getModel(
            array(
                'Db' => $adapter,
                'Database\Table\ClientsAndGroups' => $clientsAndGroups,
            )
        );
        $model->createGroup('name', 'description');
    }

    public function testDeleteGroup()
    {
        $group = Mockery::mock(Group::class);
        $group->shouldReceive('lock')->andReturn(true)->ordered();
        $group->shouldReceive('offsetGet')->with('Id')->andReturn(42);
        $group->shouldReceive('unlock')->once()->ordered();

        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('beginTransaction')->once()->ordered();
        $connection->shouldReceive('commit')->ordered();
        $connection->shouldNotReceive('rollBack');

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);

        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->once())->method('delete')->with(['group_id' => 42]);

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->expects($this->once())->method('delete')->with(['hardware_id' => 42]);

        $groupInfo = $this->createMock(GroupInfo::class);
        $groupInfo->expects($this->once())->method('delete')->with(['hardware_id' => 42]);

        $clientsAndGroups = $this->createMock(ClientsAndGroups::class);
        $clientsAndGroups->expects($this->once())->method('delete')->with(['id' => 42]);

        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')->willReturnMap([
            ['Db', $adapter],
            [GroupMemberships::class, $groupMemberships],
            [ClientConfig::class, $clientConfig],
            [GroupInfo::class, $groupInfo],
            [ClientsAndGroups::class, $clientsAndGroups],
        ]);

        $model = new GroupManager($serviceManager);
        $model->deleteGroup($group);
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
        $group = Mockery::mock(Group::class);
        $group->shouldReceive('lock')->andReturn(true)->ordered();
        $group->shouldReceive('offsetGet');
        $group->shouldReceive('unlock')->once()->ordered();

        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('beginTransaction')->once()->ordered();
        $connection->shouldNotReceive('commit');
        $connection->shouldReceive('rollBack')->once()->ordered();

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);

        $exception = new RuntimeException('database error');

        $groupMemberships = $this->createStub(GroupMemberships::class);
        $groupMemberships->method('delete')->willThrowException($exception);

        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')->willReturnMap([
            ['Db', $adapter],
            [GroupMemberships::class, $groupMemberships],
        ]);

        $this->expectExceptionObject($exception);

        $model = new GroupManager($serviceManager);
        $model->deleteGroup($group);
    }

    public function testUpdateCache()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->once())->method('update')->with(true);

        $model = $this->createPartialMock(GroupManager::class, ['getGroups']);
        $model->method('getGroups')->with('Expired')->willReturn(new ArrayIterator([$group]));
        $model->updateCache();
    }
}
