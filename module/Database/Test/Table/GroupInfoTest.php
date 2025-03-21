<?php

/**
 * Tests for the GroupInfo table
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

namespace Database\Test\Table;

use Database\Table\Config as ConfigTable;
use Laminas\Db\Adapter\Adapter;
use Model\Config as ConfigModel;
use Model\Config;
use Model\Group\Group;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GroupInfoTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        // GroupInfo initialization depends on Config table
        static::createServiceManager()->get(ConfigTable::class)->updateSchema(true);
        parent::setUpBeforeClass();
    }

    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testHydrator()
    {
        $nada = $this->createStub(\Nada\Database\AbstractDatabase::class);
        $nada->method('timestampFormatPhp')->willReturn(DATE_ATOM);

        $config = $this->createMock(ConfigModel::class);
        $config->expects($this->once())->method('__get')->with('groupCacheExpirationInterval')->willReturn(42);

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [AbstractDatabase::class, $nada],
            [Adapter::class, $this->createMock(Adapter::class)],
            [Config::class, $config],
            [Group::class, new Group()],
            [LoggerInterface::class, $this->createStub(LoggerInterface::class)],
        ]);

        $table = new \Database\Table\GroupInfo($serviceManager);

        $hydrator = $table->getHydrator();
        $this->assertInstanceOf(\Laminas\Hydrator\ArraySerializableHydrator::class, $hydrator);

        $map = $hydrator->getNamingStrategy();
        $this->assertInstanceOf('Database\Hydrator\NamingStrategy\MapNamingStrategy', $map);

        $this->assertEquals('Id', $map->hydrate('id'));
        $this->assertEquals('Name', $map->hydrate('name'));
        $this->assertEquals('Description', $map->hydrate('description'));
        $this->assertEquals('CreationDate', $map->hydrate('lastdate'));
        $this->assertEquals('DynamicMembersSql', $map->hydrate('request'));
        $this->assertEquals('CacheCreationDate', $map->hydrate('create_time'));
        $this->assertEquals('CacheExpirationDate', $map->hydrate('revalidate_from'));

        $this->assertEquals('id', $map->extract('Id'));
        $this->assertEquals('name', $map->extract('Name'));
        $this->assertEquals('description', $map->extract('Description'));
        $this->assertEquals('lastdate', $map->extract('CreationDate'));
        $this->assertEquals('request', $map->extract('DynamicMembersSql'));
        $this->assertEquals('create_time', $map->extract('CacheCreationDate'));
        $this->assertEquals('revalidate_from', $map->extract('CacheExpirationDate'));

        $dateTimeFormatterStrategy = $hydrator->getStrategy('CreationDate');
        $this->assertInstanceOf(
            'Laminas\Hydrator\Strategy\DateTimeFormatterStrategy',
            $dateTimeFormatterStrategy
        );
        $this->assertSame($dateTimeFormatterStrategy, $hydrator->getStrategy('lastdate'));

        $cacheCreationDateStrategy = $hydrator->getStrategy('CacheCreationDate');
        $this->assertInstanceOf('Database\Hydrator\Strategy\Groups\CacheDate', $cacheCreationDateStrategy);
        $this->assertSame($cacheCreationDateStrategy, $hydrator->getStrategy('create_time'));
        $this->assertEquals(0, $cacheCreationDateStrategy->offset);

        $cacheExpirationDateStrategy = $hydrator->getStrategy('CacheExpirationDate');
        $this->assertInstanceOf('Database\Hydrator\Strategy\Groups\CacheDate', $cacheExpirationDateStrategy);
        $this->assertSame($cacheExpirationDateStrategy, $hydrator->getStrategy('revalidate_from'));
        $this->assertEquals(42, $cacheExpirationDateStrategy->offset);

        $resultSet = $table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertSame($hydrator, $resultSet->getHydrator());
        $this->assertInstanceOf('Model\Group\Group', $resultSet->getObjectPrototype());
    }
}
