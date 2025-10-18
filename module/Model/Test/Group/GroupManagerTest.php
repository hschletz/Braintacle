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
use Laminas\Db\Adapter\Adapter;
use Mockery;
use Model\Group\GroupManager;
use Model\Test\AbstractTestCase;
use Psr\Container\ContainerInterface;

class GroupManagerTest extends AbstractTestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
            [GroupInfo::class, static::$serviceManager->get(GroupInfo::class)],
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
            [GroupInfo::class, static::$serviceManager->get(GroupInfo::class)],
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
