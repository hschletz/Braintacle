<?php

/**
 * Tests for the NetworkDevicesScanned table
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

use Database\Hydrator\NamingStrategy\MapNamingStrategy;
use Laminas\Hydrator\Strategy\DateTimeFormatterStrategy;
use Library\Hydrator\Strategy\MacAddress;

class NetworkDevicesScannedTest extends AbstractTest
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
        $this->assertInstanceOf(MapNamingStrategy::class, $map);

        $this->assertEquals('IpAddress', $map->hydrate('ip'));
        $this->assertEquals('MacAddress', $map->hydrate('mac'));
        $this->assertEquals('Hostname', $map->hydrate('name'));
        $this->assertEquals('DiscoveryDate', $map->hydrate('date'));
        $this->assertEquals('Description', $map->hydrate('description'));
        $this->assertEquals('Type', $map->hydrate('type'));

        $this->assertEquals('ip', $map->extract('IpAddress'));
        $this->assertEquals('mac', $map->extract('MacAddress'));
        $this->assertEquals('name', $map->extract('Hostname'));
        $this->assertEquals('date', $map->extract('DiscoveryDate'));
        $this->assertEquals('description', $map->extract('Description'));
        $this->assertEquals('type', $map->extract('Type'));

        $dateTimeFormatter = $hydrator->getStrategy('DiscoveryDate');
        $this->assertInstanceOf('Laminas\Hydrator\Strategy\DateTimeFormatterStrategy', $dateTimeFormatter);
        $this->assertEquals(
            new \DateTime('2015-11-21 19:00:00+00'),
            $dateTimeFormatter->hydrate('2015-11-21 19:00:00', null)
        );
        $dateTimeFormatter = $hydrator->getStrategy('date');
        $this->assertEquals(
            new \DateTime('2015-11-21 19:00:00+00'),
            $dateTimeFormatter->hydrate('2015-11-21 19:00:00', null)
        );
        $this->assertInstanceOf(DateTimeFormatterStrategy::class, $dateTimeFormatter);
        $this->assertInstanceOf(MacAddress::class, $hydrator->getStrategy('MacAddress'));
        $this->assertInstanceOf(MacAddress::class, $hydrator->getStrategy('mac'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
