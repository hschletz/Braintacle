<?php

/**
 * Tests for the RegistryValueDefinitions table
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

class RegistryValueDefinitionsTest extends AbstractTestCase
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

        $this->assertEquals('Id', $map->hydrate('id'));
        $this->assertEquals('Name', $map->hydrate('name'));
        $this->assertEquals('RootKey', $map->hydrate('regtree'));
        $this->assertEquals('SubKeys', $map->hydrate('regkey'));
        $this->assertEquals('Value', $map->hydrate('regvalue'));

        $this->assertEquals('id', $map->extract('Id'));
        $this->assertEquals('name', $map->extract('Name'));
        $this->assertEquals('regtree', $map->extract('RootKey'));
        $this->assertEquals('regkey', $map->extract('SubKeys'));
        $this->assertEquals('regvalue', $map->extract('Value'));

        $this->assertInstanceOf(
            'Database\Hydrator\Strategy\RegistryValueDefinitions\Value',
            $hydrator->getStrategy('Value')
        );
        $this->assertInstanceOf(
            'Database\Hydrator\Strategy\RegistryValueDefinitions\Value',
            $hydrator->getStrategy('regvalue')
        );

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
