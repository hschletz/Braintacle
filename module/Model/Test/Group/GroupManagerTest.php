<?php
/**
 * Tests for Model\Group\GroupManager
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

namespace Model\Test\Group;

class GroupManagerTest extends AbstractGroupTest
{
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
        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Table\GroupInfo', $this->_groupInfo),
                array('Library\Now', new \DateTime('2015-02-08 19:36:29')),
                array('Model\Config', $this->_config),
            )
        );

        $model = $this->getMockBuilder('Model\Group\GroupManager')
                      ->setMethods(array('updateCache'))
                      ->setConstructorArgs(array($serviceManager))
                      ->getMock();
        $model->expects($this->$updateCache())->method('updateCache');

        $resultSet = $model->getGroups($filter, $filterArg, $order, $direction);
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $resultSet);
        $groups = iterator_to_array($resultSet);
        $this->assertContainsOnlyInstancesOf('Model\Group\Group', $groups);
        $this->assertCount(count($expected), $groups);
        foreach ($groups as $index => $group) {
            $this->assertEquals($expected[$index], $group->getArrayCopy());
        }
    }

    public function testGetGroupsInvalidFilter()
    {
        $this->expectException('InvalidArgumentException', 'Invalid group filter: invalid');
        $model = $this->_getModel(array('Database\Table\GroupInfo' => $this->_groupInfo));
        $resultSet = $model->getGroups('invalid');
    }

    public function testGetGroup()
    {
        $model = $this->_getModel(array('Database\Table\GroupInfo' => $this->_groupInfo));
        $group = $model->getGroup('name2');
        $this->assertInstanceOf('Model\Group\Group', $group);
        $this->assertEquals('name2', $group['Name']);
    }

    public function testGetGroupNonExistentGroup()
    {
        $this->expectException('RuntimeException', 'Unknown group name: invalid');
        $model = $this->_getModel(array('Database\Table\GroupInfo' => $this->_groupInfo));
        $group = $model->getGroup('invalid');
    }

    public function testGetGroupNoName()
    {
        $this->expectException('InvalidArgumentException', 'No group name given');
        $model = $this->_getModel();
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
        $model = $this->_getModel(
            array(
                'Database\Table\GroupInfo' => $this->_groupInfo,
                'Library\Now' => new \DateTime('2015-02-12 22:07:00'),
            )
        );
        $model->createGroup('name3', $description);

        $table = static::$serviceManager->get('Database\Table\ClientsAndGroups');
        $id = $table->select(array('name' => 'name3', 'deviceid' => '_SYSTEMGROUP_'))->current()['id'];
        $dataSet = new \PHPUnit\DbUnit\DataSet\ReplacementDataSet($this->_loadDataSet('CreateGroup'));
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
        $model = $this->_getModel();
        try {
            $model->createGroup('');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Group name is empty', $e->getMessage());
            $dataSet = $this->_loadDataSet();
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
        $model = $this->_getModel();
        try {
            $model->createGroup('name2');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Group already exists: name2', $e->getMessage());
            $dataSet = $this->_loadDataSet();
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
        $connection = $this->createMock('Zend\Db\Adapter\Driver\AbstractConnection');
        $connection->expects($this->at(0))->method('beginTransaction');
        $connection->expects($this->at(1))->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Zend\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Zend\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $resultSet = $this->createMock('Zend\Db\ResultSet\AbstractResultSet');
        $resultSet->method('count')->willReturn(0);

        $clientsAndGroups = $this->createMock('Database\Table\ClientsAndGroups');
        $clientsAndGroups->method('select')->willReturn($resultSet);
        $clientsAndGroups->method('insert')->willThrowException(new \RuntimeException('test message'));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $model = $this->_getModel(
            array(
                'Db' => $adapter,
                'Database\Table\ClientsAndGroups' => $clientsAndGroups,
            )
        );
        $model->createGroup('name', 'description');
    }

    public function testDeleteGroup()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->at(0))->method('lock')->willReturn(true);
        $group->expects($this->at(1))->method('offsetGet')->with('Id')->willReturn(1);
        $group->expects($this->at(2))->method('unlock');

        $model = $this->_getModel(array('Database\Table\GroupInfo' => $this->_groupInfo));
        $model->deleteGroup($group);

        $dataSet = $this->_loadDataSet('DeleteGroup');
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
        $group->expects($this->at(0))->method('lock')->willReturn(false);

        $model = $this->_getModel();
        try {
            $model->deleteGroup($group);
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Group\RuntimeException $e) {
            $this->assertEquals('Cannot delete group because it is locked', $e->getMessage());
            $dataSet = $this->_loadDataSet();
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
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->at(0))->method('lock')->willReturn(true);

        $clientsAndGroups = $this->createMock('Database\Table\ClientsAndGroups');
        $clientsAndGroups->method('delete')->will($this->throwException(new \RuntimeException('database error')));

        $model = $this->_getModel(
            array(
                'Database\Table\ClientsAndGroups' => $clientsAndGroups,
                'Database\Table\GroupInfo' => $this->_groupInfo,
            )
        );
        try {
            $model->deleteGroup($group);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('database error', $e->getMessage());
            $dataSet = $this->_loadDataSet();
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

        $model = $this->getMockBuilder($this->_getClass())
                      ->disableOriginalConstructor()
                      ->setMethods(array('getGroups'))
                      ->getMock();
        $model->expects($this->once())->method('getGroups')->with('Expired')->willReturn(array($group));
        $model->updateCache();
    }
}
