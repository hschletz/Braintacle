<?php

/**
 * Tests for the Software table
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

use Database\Table\Software;

class SoftwareTest extends AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testHydrator()
    {
        $hydrator = static::$_table->getHydrator();
        $this->assertInstanceOf('Database\Hydrator\Software', $hydrator);

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }

    public function testDelete()
    {
        $softwareRaw = $this->createMock(\Database\Table\SoftwareRaw::class);
        $softwareRaw->method('delete')->with('_where')->willReturn(42);

        $serviceLocator = $this->createMock(\Laminas\ServiceManager\ServiceLocatorInterface::class);
        $serviceLocator->method('get')->with('Database\Table\SoftwareRaw')->willReturn($softwareRaw);

        $table = $this->createPartialMock(Software::class, ['getServiceLocator']);
        $table->method('getServiceLocator')->willReturn($serviceLocator);

        $this->assertEquals(42, $table->delete('_where'));
    }
}
