<?php

/**
 * Tests for IpNetworkAddress validator
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

namespace Library\Test\Validator;

use Library\Validator\IpNetworkAddress;

/**
 * Tests for IpNetworkAddress validator
 */
class IpNetworkAddressTest extends \PHPUnit\Framework\TestCase
{
    public function validAddressProvider()
    {
        return [
            ['192.0.2.0/0.0.0.0'],
            ['192.0.2.0/255.255.0.0'],
            ['192.0.2.0/255.255.255.255'],
            ['192.0.2.0/0'],
            ['192.0.2.0/16'],
            ['192.0.2.0/32'],
            ['2001:db8::/0'],
            ['2001:db8::/32'],
            ['2001:db8::/128'],
        ];
    }

    /** @dataProvider validAddressProvider */
    public function testValidAddress($value)
    {
        $validator = new IpNetworkAddress();
        $this->assertTrue($validator->isValid($value));
    }

    public function invalidAddressProvider()
    {
        return [
            ['192.0.2.0', IpNetworkAddress::FORMAT],
            ['192.0.2.0/0/0', IpNetworkAddress::FORMAT],
            ['192.0.2.0/', IpNetworkAddress::MASK],
            ['192.0.2.0/0.0.0', IpNetworkAddress::MASK],
            ['192.0.2.0/0.255.0.0', IpNetworkAddress::MASK],
            ['/0', IpNetworkAddress::ADDRESS],
            ['192.0.2/0', IpNetworkAddress::ADDRESS],
            ['2001:db8/0', IpNetworkAddress::ADDRESS],
            ['/0', IpNetworkAddress::ADDRESS],
            ['192.0.2.0/33', IpNetworkAddress::MASK],
            ['192.0.2.0/', IpNetworkAddress::MASK],
            ['2001:db8::/2001:db8::', IpNetworkAddress::MASK],
            ['2001:db8::/129', IpNetworkAddress::MASK],
            ['2001:db8::/', IpNetworkAddress::MASK],
        ];
    }

    /** @dataProvider invalidAddressProvider */
    public function testInvalidAddress($value, $message)
    {
        $validator = new IpNetworkAddress();
        $this->assertFalse($validator->isValid($value));

        $messages = $validator->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey($message, $messages);
    }
}
