<?php
/**
 * Tests for Model\Network\SubnetManager
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

    public function invalidArgumentsProvider()
    {
        return array(
            array('192.0.2.0', '2001:db8::', 'Not an IPv4 mask'),
            array('2001:db8::', '255.255.255.128', 'Not an IPv4 address'),
            array('192.0.2.0', null, 'Not an IPv4 mask'),
            array('192.0.2.0', '', 'Not an IPv4 mask'),
            array(null, '255.255.255.128', 'Not an IPv4 address'),
            array('', '255.255.255.128', 'Not an IPv4 address'),
            array('abc', '255.255.255.0', 'Not an IPv4 address'),
            array('192.0.2.0', '0.0.0.0.0', 'Not an IPv4 mask'),
            array('192.0.2.0', '255.0.255.0', 'Not an IPv4 mask'),
            array('192.0.2.0', '0.255.255.0', 'Not an IPv4 mask'),
        );
    }

    public function getSubnetsProvider()
    {
        $subnet1 = array(
          'Address' => '192.0.2.0',
          'Mask' => '255.255.255.0',
          'NumInventoried' => '1',
          'NumIdentified' => '0',
          'NumUnknown' => '0',
          'Name' => null,
        );
        $subnet2 = array(
          'Address' => '192.0.2.0',
          'Mask' => '255.255.255.128',
          'NumInventoried' => '1',
          'NumIdentified' => '1',
          'NumUnknown' => '1',
          'Name' => 'NAME',
        );
        return array(
            array('NumIdentified', 'asc', $subnet1, $subnet2),
            array('NumIdentified', 'invalid', $subnet1, $subnet2), // becomes 'ASC'
            array('NumIdentified', 'desc', $subnet2, $subnet1),
            array('CidrAddress', 'desc', $subnet2, $subnet1), // IP addresses lexically sorted as strings
        );
    }

    /**
     * @dataProvider getSubnetsProvider
     */
    public function testGetSubnets($order, $direction, $subnet1, $subnet2)
    {
        $model = $this->_getModel();
        $subnets = $model->getSubnets($order, $direction);
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $subnets);
        $subnets = iterator_to_array($subnets);
        $this->assertCount(2, $subnets);
        $this->assertContainsOnlyInstancesOf('Model\Network\Subnet', $subnets);
        $this->assertEquals($subnet1, $subnets[0]->getArrayCopy());
        $this->assertEquals($subnet2, $subnets[1]->getArrayCopy());
    }

    public function testGetSubnetsNoOrdering()
    {
        $model = $this->_getModel();
        $subnets = $model->getSubnets();
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $subnets);
        $subnets = iterator_to_array($subnets);
        $this->assertCount(2, $subnets);
        $this->assertContainsOnlyInstancesOf('Model\Network\Subnet', $subnets);
    }

    public function getSubnetProvider()
    {
        return array(
            array('203.0.113.0', '255.255.255.128', null), // Does not exist
            array('203.0.113.1', '255.255.255.255', null), // Does not exist, mask should be valid
            array('203.0.113.1', '0.0.0.0', null), // Does not exist, mask should be valid
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
        $this->assertInstanceOf('Model\Network\Subnet', $subnet);
        $this->assertEquals(
            array (
              'Address' => $address,
              'Mask' => $mask,
              'Name' => $name,
            ),
            $subnet->getArrayCopy()
        );
    }

    /**
     * @dataProvider invalidArgumentsProvider
     */
    public function testGetSubnetInvalidArguments($address, $mask, $message)
    {
        $model = $this->_getModel();
        try {
            $model->getSubnet($address, $mask);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith($message, $e->getMessage());
        }
    }

    public function saveSubnetProvider()
    {
        return array(
            array('192.0.2.0', '255.255.255.0', 'new_name', 'SaveSubnetInsertWithName'),
            array('192.0.2.0', '255.255.255.0', '', 'SaveSubnetInsertWithoutName'),
            array('192.0.2.0', '255.255.255.0', null, 'SaveSubnetInsertWithoutName'),
            array('192.0.2.0', '255.255.255.128', 'new_name', 'SaveSubnetUpdateWithName'),
            array('192.0.2.0', '255.255.255.128', '', 'SaveSubnetUpdateWithoutName'),
            array('192.0.2.0', '255.255.255.128', null, 'SaveSubnetUpdateWithoutName'),
        );
    }

    /**
     * @dataProvider saveSubnetProvider
     */
    public function testSaveSubnet($address, $mask, $name, $dataSet)
    {
        $model = $this->_getModel();
        $model->saveSubnet($address, $mask, $name, $dataSet);
        $this->assertTablesEqual(
            $this->_loadDataset($dataSet)->getTable('subnet'),
            $this->getConnection()->createQueryTable('subnet', 'SELECT netid, mask, name FROM subnet')
        );
    }

    /**
     * @dataProvider invalidArgumentsProvider
     */
    public function testSaveSubnetInvalidArguments($address, $mask, $message)
    {
        $model = $this->_getModel();
        try {
            $model->saveSubnet($address, $mask, null);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith($message, $e->getMessage());
        }
    }
}
