<?php
/**
 * Tests for Model\Network\SubnetManager
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

namespace Model\Test\Network;

/**
 * Tests for Model\Network\SubnetManager
 */
class SubnetManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array(
        'NetworkDevicesIdentified',
        'NetworkDevicesScanned',
        'NetworkDeviceTypes',
        'NetworkInterfaces',
        'Subnets',
    );

    public function testGetSubnets()
    {
        $model = $this->_getModel();
        $subnets = $model->getSubnets('NumIdentified', 'desc');
        $this->assertInstanceOf('Zend\Db\ResultSet\ResultSetInterface', $subnets);
        $subnets->buffer();
        $this->assertCount(2, $subnets);
        $this->assertContainsOnlyInstancesOf('Model_Subnet', $subnets);
        $this->assertEquals(
            array(
                array (
                  'Address' => '192.0.2.0',
                  'Mask' => '255.255.255.128',
                  'NumInventoried' => '1',
                  'NumIdentified' => '1',
                  'NumUnknown' => '1',
                  'Name' => 'NAME',
                ),
                array (
                  'Address' => '192.0.2.0',
                  'Mask' => '255.255.255.0',
                  'NumInventoried' => '1',
                  'NumIdentified' => '0',
                  'NumUnknown' => '0',
                  'Name' => null,
                ),
            ),
            $subnets->toArray()
        );
    }

    public function getSubnetProvider()
    {
        return array(
            array('203.0.113.0', '255.255.255.128', null), // Does not exist
            array('192.0.2.0', '255.255.255.0', null), // Exists, no properties
            array('192.0.2.0', '255.255.255.128', 'NAME'), // Exists with properties
        );
    }

    /**
     * @dataProvider getSubnetProvider
     */
    public function testGetSubnet($address, $mask, $name)
    {
        $model = $this->_getModel();
        $subnet = $model->getSubnet($address, $mask);
        $this->assertInstanceOf('Model_Subnet', $subnet);
        $this->assertEquals(
            array (
              'Address' => $address,
              'Mask' => $mask,
              'Name' => $name,
            ),
            $subnet->getArrayCopy()
        );
    }

    public function getSubnetInvalidArgumentsProvider()
    {
        return array(
            array('192.0.2.0', null),
            array(null, '255.255.255.128'),
            array('abc', '255.255.255.0'),
            array('192.0.2.0', '0.0.0.0.0'),
        );
    }

    /**
     * @dataProvider getSubnetInvalidArgumentsProvider
     */
    public function testGetSubnetInvalidArguments($address, $mask)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid address/mask');
        $model = $this->_getModel();
        $model->getSubnet($address, $mask);
    }
}
