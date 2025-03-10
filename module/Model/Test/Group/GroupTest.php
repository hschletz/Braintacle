<?php

/**
 * Tests for Model\Group\Group
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

use Database\Table\Clients;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use DateTimeImmutable;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform\AbstractPlatform;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\ClientManager;
use Model\Config;
use Model\Group\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Random\Randomizer;

class GroupTest extends AbstractGroupTestCase
{
    use MockeryPHPUnitIntegration;

    /** {@inheritdoc} */
    protected static $_tables = array(
        'ClientsAndGroups',
        'ClientSystemInfo',
        'Clients',
        'GroupMemberships',
    );

    public static function getDefaultConfigProvider()
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
        static::$serviceManager->setService(Config::class, $config);

        $model = new Group();
        $model->setContainer(static::$serviceManager);

        $this->assertSame($expectedValue, $model->getDefaultConfig($option));
    }

    public static function setMembersFromQueryProvider()
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
                true,
                false,
                true,
                true
            )->willReturn(array(array('Id' => 1), array('Id' => 2), array('Id' => 3), array('Id' => 5)));

        $serviceManager = $this->createStub(ContainerInterface::class);
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
        $model->setContainer($serviceManager);

        $model->setMembersFromQuery($type, 'filter', 'search', 'operator', true);
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

        $serviceManager = $this->createStub(ContainerInterface::class);
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
        $model->setContainer($serviceManager);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test');
        $model->setMembersFromQuery(\Model\Client\Client::MEMBERSHIP_ALWAYS, 'filter', 'search', 'operator', true);
    }

    public static function setMembersFromQueryDynamicProvider()
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
                true,
                false,
                true,
                false
            )->willReturn($select);

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [Adapter::class, $adapter],
            [ClientManager::class, $clientManager],
            [GroupInfo::class, $this->_groupInfo]
        ]);

        $model = $this->createPartialMock(Group::class, ['update']);
        $model->expects($this->once())
            ->method('update')
            ->with(true)
            ->willReturnCallback(function () use ($model) {
                // Verify that value is set before update() gets called
                $this->assertEquals('query_new', $model['DynamicMembersSql']);
            });
        $model['Id'] = 10;
        $model->setContainer($serviceManager);

        $model->setMembersFromQuery(
            \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
            'filter',
            'search',
            'operator',
            true
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
        static::$serviceManager->setService(ClientManager::class, $clientManager);

        $model = new Group();
        $model->setContainer(static::$serviceManager);
        $model->id = 10;

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Expected 1 column, got 2');
        $model->setMembersFromQuery(
            \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
            'filter',
            'search',
            'operator',
            true
        );
    }

    public static function updateProvider()
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
        $now = new DateTimeImmutable('2015-07-23 20:20:00');

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        // Builtin Randomizer class final and cannot be mocked. Create proxy instead.
        $randomizer = Mockery::mock(new Randomizer());
        $randomizer->shouldReceive('getInt')->with(0, 60)->andReturn(42);

        $config = $this->createMock('Model\Config');
        $config->method('__get')->will(
            $this->returnValueMap(
                array(
                    array('groupCacheExpirationInterval', 600),
                    array('groupCacheExpirationFuzz', 60),
                )
            )
        );

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap([
                [Clients::class, static::$serviceManager->get(Clients::class)],
                [GroupInfo::class, $this->_groupInfo],
                [GroupMemberships::class, static::$serviceManager->get(GroupMemberships::class)],
                [ClockInterface::class, $clock],
                [Randomizer::class, $randomizer],
                [Config::class, $config],
            ]);

        $model = $this->createPartialMock(Group::class, ['lock', 'unlock']);
        $model->method('lock')->willReturn($lockSuccess);
        if ($dataSet !== null) {
            $model->expects($this->once())->method('unlock');
        }
        $model->setContainer($serviceManager);
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
        $model = new Group();
        $model->setContainer(static::$serviceManager);
        $model->id = 10;
        $this->assertEquals(array('package1', 'package2'), $model->getPackages());
    }

    public function testGetPackagesReverseOrder()
    {
        $model = new Group();
        $model->setContainer(static::$serviceManager);
        $model->id = 10;
        $this->assertEquals(array('package2', 'package1'), $model->getPackages('desc'));
    }
}
