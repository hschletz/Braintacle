<?php
/**
 * Tests for the WindowsInstallations table
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

class WindowsInstallationsTest extends AbstractTest
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

        $this->assertEquals('UserDomain', $map->hydrate('userdomain'));
        $this->assertEquals('Company', $map->hydrate('wincompany'));
        $this->assertEquals('Owner', $map->hydrate('winowner'));
        $this->assertEquals('ProductKey', $map->hydrate('winprodkey'));
        $this->assertEquals('ProductId', $map->hydrate('winprodid'));
        $this->assertEquals('ManualProductKey', $map->hydrate('manual_product_key'));

        $this->assertEquals('userdomain', $map->extract('UserDomain'));
        $this->assertEquals('wincompany', $map->extract('Company'));
        $this->assertEquals('winowner', $map->extract('Owner'));
        $this->assertEquals('winprodkey', $map->extract('ProductKey'));
        $this->assertEquals('winprodid', $map->extract('ProductId'));
        $this->assertEquals('manual_product_key', $map->extract('ManualProductKey'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Zend\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
