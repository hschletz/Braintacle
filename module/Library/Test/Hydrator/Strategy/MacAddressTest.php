<?php

/**
 * Tests for the MacAddress strategy
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

namespace Library\Test\Hydrator\Strategy;

class MacAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testHydrate()
    {
        $strategy = new \Library\Hydrator\Strategy\MacAddress();
        $address = '00:00:5E:00:53:00';
        $macAddress = $strategy->hydrate($address, null);
        $this->assertInstanceOf('Library\MacAddress', $macAddress);
        $this->assertEquals($address, $macAddress);
    }

    public function testExtract()
    {
        $strategy = new \Library\Hydrator\Strategy\MacAddress();
        $address = '00:00:5E:00:53:00';
        $macAddress = new \Library\MacAddress($address);
        $this->assertSame($address, $strategy->extract($macAddress));
    }
}
