<?php

/**
 * Tests for the AndroidInstallations table
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

class AndroidInstallationsTest extends AbstractTest
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

        $this->assertEquals('Country', $map->hydrate('javacountry'));
        $this->assertEquals('JavaVm', $map->hydrate('javaname'));
        $this->assertEquals('JavaInstallationDirectory', $map->hydrate('javahome'));
        $this->assertEquals('JavaClassPath', $map->hydrate('javaclasspath'));

        $this->assertEquals('javacountry', $map->extract('Country'));
        $this->assertEquals('javaname', $map->extract('JavaVm'));
        $this->assertEquals('javahome', $map->extract('JavaInstallationDirectory'));
        $this->assertEquals('javaclasspath', $map->extract('JavaClassPath'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Model\Client\AndroidInstallation', $resultSet->getObjectPrototype());
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
