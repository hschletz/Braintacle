<?php
/**
 * Tests for the Packages table
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

/**
 * Tests for the Packages table
 */
class PackagesTest extends AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet;
    }

    public function testHydrator()
    {
        $hydrator = static::$_table->getHydrator();
        $this->assertInstanceOf('Zend\Stdlib\Hydrator\ArraySerializable', $hydrator);

        $map = $hydrator->getNamingStrategy();
        $this->assertInstanceOf('Database\Hydrator\NamingStrategy\MapNamingStrategy', $map);

        $this->assertEquals('Name', $map->hydrate('name'));
        $this->assertEquals('Id', $map->hydrate('fileid'));
        $this->assertEquals('Priority', $map->hydrate('priority'));
        $this->assertEquals('NumFragments', $map->hydrate('fragments'));
        $this->assertEquals('Size', $map->hydrate('size'));
        $this->assertEquals('Platform', $map->hydrate('osname'));
        $this->assertEquals('Comment', $map->hydrate('comment'));
        $this->assertEquals('NumNonnotified', $map->hydrate('num_nonnotified'));
        $this->assertEquals('NumSuccess', $map->hydrate('num_success'));
        $this->assertEquals('NumNotified', $map->hydrate('num_notified'));
        $this->assertEquals('NumError', $map->hydrate('num_error'));

        $this->assertEquals('name', $map->extract('Name'));
        $this->assertEquals('fileid', $map->extract('Id'));
        $this->assertEquals('priority', $map->extract('Priority'));
        $this->assertEquals('fragments', $map->extract('NumFragments'));
        $this->assertEquals('size', $map->extract('Size'));
        $this->assertEquals('osname', $map->extract('Platform'));
        $this->assertEquals('comment', $map->extract('Comment'));
        $this->assertEquals('num_nonnotified', $map->extract('NumNonnotified'));
        $this->assertEquals('num_success', $map->extract('NumSuccess'));
        $this->assertEquals('num_notified', $map->extract('NumNotified'));
        $this->assertEquals('num_error', $map->extract('NumError'));

        $this->assertInstanceOf('Database\Hydrator\Strategy\Packages\Platform', $hydrator->getStrategy('Platform'));
        $this->assertInstanceOf('Database\Hydrator\Strategy\Packages\Platform', $hydrator->getStrategy('osname'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Zend\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}