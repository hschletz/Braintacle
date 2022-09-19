<?php

/**
 * Tests for the Cpu table
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

namespace Database\Test\Table;

class CpuTest extends AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testHydrator()
    {
        $hydrator = static::$_table->getHydrator();
        $this->assertInstanceOf(\Laminas\Hydrator\ArraySerializableHydrator::class, $hydrator);

        $map = $hydrator->getNamingStrategy();
        $this->assertInstanceOf('Database\Hydrator\NamingStrategy\MapNamingStrategy', $map);

        $this->assertEquals('Manufacturer', $map->hydrate('manufacturer'));
        $this->assertEquals('Type', $map->hydrate('type'));
        $this->assertEquals('NumCores', $map->hydrate('cores'));
        $this->assertEquals('Architecture', $map->hydrate('cpuarch'));
        $this->assertEquals('DataWidth', $map->hydrate('data_width'));
        $this->assertEquals('L2CacheSize', $map->hydrate('l2cachesize'));
        $this->assertEquals('SocketType', $map->hydrate('socket'));
        $this->assertEquals('NominalClock', $map->hydrate('speed'));
        $this->assertEquals('CurrentClock', $map->hydrate('current_speed'));
        $this->assertEquals('Voltage', $map->hydrate('voltage'));
        $this->assertEquals('Serial', $map->hydrate('serialnumber'));

        $this->assertEquals('manufacturer', $map->extract('Manufacturer'));
        $this->assertEquals('type', $map->extract('Type'));
        $this->assertEquals('cores', $map->extract('NumCores'));
        $this->assertEquals('cpuarch', $map->extract('Architecture'));
        $this->assertEquals('data_width', $map->extract('DataWidth'));
        $this->assertEquals('l2cachesize', $map->extract('L2CacheSize'));
        $this->assertEquals('socket', $map->extract('SocketType'));
        $this->assertEquals('speed', $map->extract('NominalClock'));
        $this->assertEquals('current_speed', $map->extract('CurrentClock'));
        $this->assertEquals('voltage', $map->extract('Voltage'));
        $this->assertEquals('serialnumber', $map->extract('Serial'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertInstanceOf('Model\Client\Item\Cpu', $resultSet->getObjectPrototype());
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
