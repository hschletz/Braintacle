<?php

/**
 * Tests for the MemorySlots table
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

class MemorySlotsTest extends AbstractTest
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

        $this->assertEquals('SlotNumber', $map->hydrate('numslots'));
        $this->assertEquals('Type', $map->hydrate('type'));
        $this->assertEquals('Size', $map->hydrate('capacity'));
        $this->assertEquals('Clock', $map->hydrate('speed'));
        $this->assertEquals('Caption', $map->hydrate('caption'));
        $this->assertEquals('Description', $map->hydrate('description'));
        $this->assertEquals('Serial', $map->hydrate('serialnumber'));

        $this->assertEquals('numslots', $map->extract('SlotNumber'));
        $this->assertEquals('type', $map->extract('Type'));
        $this->assertEquals('capacity', $map->extract('Size'));
        $this->assertEquals('speed', $map->extract('Clock'));
        $this->assertEquals('caption', $map->extract('Caption'));
        $this->assertEquals('description', $map->extract('Description'));
        $this->assertEquals('serialnumber', $map->extract('Serial'));

        $this->assertInstanceOf('Database\Hydrator\Strategy\MemorySlots\Size', $hydrator->getStrategy('Size'));
        $this->assertInstanceOf('Database\Hydrator\Strategy\MemorySlots\Size', $hydrator->getStrategy('capacity'));
        $this->assertInstanceOf('Database\Hydrator\Strategy\MemorySlots\Clock', $hydrator->getStrategy('Clock'));
        $this->assertInstanceOf('Database\Hydrator\Strategy\MemorySlots\Clock', $hydrator->getStrategy('speed'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
