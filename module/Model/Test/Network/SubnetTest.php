<?php
/**
 * Tests for Model\Network\Subnet
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Test\Network;

class SubnetTest extends \Model\Test\AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet;
    }

    public function testObjectProperties()
    {
        $model = $this->_getModel();
        $this->assertInstanceOf('ArrayAccess', $model);
        $this->assertTrue(method_exists($model, 'exchangeArray'));
    }

    public function getCidrAddressProvider()
    {
        return [
            ['192.0.2.0', '0.0.0.0', 0],
            ['192.0.2.0', '255.255.0.0', 16],
            ['192.0.2.0', '255.255.255.255', 32],
            ['2001:db8::', '32', 32],
        ];
    }

    /**
     * @dataProvider getCidrAddressProvider
     */
    public function testGetCidrAddress($address, $mask, $suffix)
    {
        $model = $this->_getModel();
        $model['Address'] = $address;
        $model['Mask'] = $mask;
        $this->assertEquals("$address/$suffix", $model['CidrAddress']);
    }

    public function testGetCidrAddressInvalidAddress()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Not an IP address: invalid');
        $model = $this->_getModel();
        $model['Address'] = 'invalid';
        $model['Mask'] = '0.0.0.0';
        $model['CidrAddress'];
    }

    public function testGetCidrAddressIpV4MaskInvalidSyntax()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Not an IPv4 address mask: 255.0.555.0');
        $model = $this->_getModel();
        $model['Address'] = '192.0.2.0';
        $model['Mask'] = '255.0.555.0';
        $model['CidrAddress'];
    }
    
    public function testGetCidrAddressNotCidrIpV4()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Not a CIDR mask: 255.0.255.0');
        $model = $this->_getModel();
        $model['Address'] = '192.0.2.0';
        $model['Mask'] = '255.0.255.0';
        $model['CidrAddress'];
    }

    public function testGetCidrAddressNotCidrIpV6()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Not a CIDR mask: ffff:ffff:ffff:ffff:0000:0000:0000:0000');
        $model = $this->_getModel();
        $model['Address'] = '2001:db8::';
        // Technically a valid mask, but this format is not supported (and not used)
        $model['Mask'] = 'ffff:ffff:ffff:ffff:0000:0000:0000:0000';
        $model['CidrAddress'];
    }
}
