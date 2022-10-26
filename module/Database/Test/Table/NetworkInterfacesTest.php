<?php

/**
 * Tests for the NetworkInterfaces table
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

use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Hydrator\ArraySerializableHydrator;
use Library\Hydrator\Strategy\MacAddress;
use Model\Client\Item\NetworkInterface;

class NetworkInterfacesTest extends AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testHydratorClass()
    {
        $hydrator = static::$_table->getHydrator();
        $this->assertInstanceOf(ArraySerializableHydrator::class, $hydrator);
    }

    public function testHydratorNamingStrategy()
    {
        $hydrator = static::$_table->getHydrator();
        $map = $hydrator->getNamingStrategy();

        $this->assertEquals('Description', $map->hydrate('description'));
        $this->assertEquals('Rate', $map->hydrate('speed'));
        $this->assertEquals('MacAddress', $map->hydrate('macaddr'));
        $this->assertEquals('IpAddress', $map->hydrate('ipaddress'));
        $this->assertEquals('Netmask', $map->hydrate('ipmask'));
        $this->assertEquals('Gateway', $map->hydrate('ipgateway'));
        $this->assertEquals('Subnet', $map->hydrate('ipsubnet'));
        $this->assertEquals('DhcpServer', $map->hydrate('ipdhcp'));
        $this->assertEquals('Status', $map->hydrate('status'));
        $this->assertEquals('Type', $map->hydrate('type'));
        $this->assertEquals('TypeMib', $map->hydrate('typemib'));
        $this->assertEquals('IsBlacklisted', $map->hydrate('is_blacklisted'));

        $this->assertEquals('description', $map->extract('Description'));
        $this->assertEquals('speed', $map->extract('Rate'));
        $this->assertEquals('macaddr', $map->extract('MacAddress'));
        $this->assertEquals('ipaddress', $map->extract('IpAddress'));
        $this->assertEquals('ipmask', $map->extract('Netmask'));
        $this->assertEquals('ipgateway', $map->extract('Gateway'));
        $this->assertEquals('ipsubnet', $map->extract('Subnet'));
        $this->assertEquals('ipdhcp', $map->extract('DhcpServer'));
        $this->assertEquals('status', $map->extract('Status'));
        $this->assertEquals('type', $map->extract('Type'));
        $this->assertEquals('typemib', $map->extract('TypeMib'));
    }

    public function testHydratorMacAddressStrategy()
    {
        $hydrator = static::$_table->getHydrator();
        $this->assertInstanceOf(MacAddress::class, $hydrator->getStrategy('MacAddress'));
        $this->assertInstanceOf(MacAddress::class, $hydrator->getStrategy('macaddr'));
    }

    public function testHydratorExtraction()
    {
        $hydrator = static::$_table->getHydrator();
        $object = new NetworkInterface();
        $object->description = '_description';
        $object->isBlacklisted = true;
        $this->assertEquals(
            ['description' => '_description'],
            $hydrator->extract($object)
        );
    }

    public function testResultSetHydrator()
    {
        $hydrator = static::$_table->getHydrator();

        /** @var HydratingResultSet */
        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf(HydratingResultSet::class, $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
