<?php

/**
 * Tests for the Subnets table
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

class SubnetsTest extends AbstractTest
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

        $this->assertEquals('Address', $map->hydrate('netid'));
        $this->assertEquals('Mask', $map->hydrate('mask'));
        $this->assertEquals('Name', $map->hydrate('name'));
        $this->assertEquals('NumInventoried', $map->hydrate('num_inventoried'));
        $this->assertEquals('NumIdentified', $map->hydrate('num_identified'));
        $this->assertEquals('NumUnknown', $map->hydrate('num_unknown'));

        $this->assertEquals('netid', $map->extract('Address'));
        $this->assertEquals('mask', $map->extract('Mask'));
        $this->assertEquals('name', $map->extract('Name'));
        $this->assertEquals('num_inventoried', $map->extract('NumInventoried'));
        $this->assertEquals('num_identified', $map->extract('NumIdentified'));
        $this->assertEquals('num_unknown', $map->extract('NumUnknown'));

        $this->assertInstanceOf('Library\Hydrator\Strategy\Integer', $hydrator->getStrategy('NumInventoried'));
        $this->assertInstanceOf('Library\Hydrator\Strategy\Integer', $hydrator->getStrategy('NumIdentified'));
        $this->assertInstanceOf('Library\Hydrator\Strategy\Integer', $hydrator->getStrategy('NumUnknown'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
