<?php

/**
 * Tests for the MsOfficeProducts table
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

class MsOfficeProductsTest extends AbstractTest
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

        $this->assertEquals('Name', $map->hydrate('product'));
        $this->assertEquals('Version', $map->hydrate('officeversion'));
        $this->assertEquals('ExtraDescription', $map->hydrate('note'));
        $this->assertEquals('Architecture', $map->hydrate('type'));
        $this->assertEquals('ProductId', $map->hydrate('productid'));
        $this->assertEquals('ProductKey', $map->hydrate('officekey'));
        $this->assertEquals('Guid', $map->hydrate('guid'));
        $this->assertEquals('Type', $map->hydrate('install'));

        $this->assertEquals('product', $map->extract('Name'));
        $this->assertEquals('officeversion', $map->extract('Version'));
        $this->assertEquals('note', $map->extract('ExtraDescription'));
        $this->assertEquals('type', $map->extract('Architecture'));
        $this->assertEquals('productid', $map->extract('ProductId'));
        $this->assertEquals('officekey', $map->extract('ProductKey'));
        $this->assertEquals('guid', $map->extract('Guid'));
        $this->assertEquals('install', $map->extract('Type'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
