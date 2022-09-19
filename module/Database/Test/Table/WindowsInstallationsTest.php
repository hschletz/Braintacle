<?php

/**
 * Tests for the WindowsInstallations table
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

class WindowsInstallationsTest extends AbstractTest
{
    public static function setUpBeforeClass(): void
    {
        // These tables must exist before the view can be created
        static::$serviceManager->get('Database\Table\ClientsAndGroups')->updateSchema(true);
        static::$serviceManager->get('Database\Table\WindowsProductKeys')->updateSchema(true);
        parent::setUpBeforeClass();
    }

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

        $this->assertEquals('Workgroup', $map->hydrate('workgroup'));
        $this->assertEquals('UserDomain', $map->hydrate('user_domain'));
        $this->assertEquals('Company', $map->hydrate('company'));
        $this->assertEquals('Owner', $map->hydrate('owner'));
        $this->assertEquals('ProductKey', $map->hydrate('product_key'));
        $this->assertEquals('ProductId', $map->hydrate('product_id'));
        $this->assertEquals('ManualProductKey', $map->hydrate('manual_product_key'));
        $this->assertEquals('CpuArchitecture', $map->hydrate('cpu_architecture'));

        $this->assertEquals('workgroup', $map->extract('Workgroup'));
        $this->assertEquals('user_domain', $map->extract('UserDomain'));
        $this->assertEquals('company', $map->extract('Company'));
        $this->assertEquals('owner', $map->extract('Owner'));
        $this->assertEquals('product_key', $map->extract('ProductKey'));
        $this->assertEquals('product_id', $map->extract('ProductId'));
        $this->assertEquals('manual_product_key', $map->extract('ManualProductKey'));
        $this->assertEquals('cpu_architecture', $map->extract('CpuArchitecture'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
