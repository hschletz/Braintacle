<?php

/**
 * Tests for Model\Network\SubnetManager
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

namespace Model\Test\Network;

use Database\Table\Subnets;
use InvalidArgumentException;
use Library\Validator\IpNetworkAddress;
use Model\Network\SubnetManager;
use PHPUnit\Framework\MockObject\MockObject;

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

    public function testGetSubnetsFullByCidrAddress()
    {
        $model = $this->getModel();
        $subnets = $model->getSubnets('CidrAddress', 'desc');
        $this->assertInstanceOf('Laminas\Db\ResultSet\AbstractResultSet', $subnets);
        $subnets = iterator_to_array($subnets);
        $this->assertCount(4, $subnets);
        $this->assertContainsOnlyInstancesOf('Model\Network\Subnet', $subnets);
        $this->assertSame(
            array(
                'Address' => '203.0.113.0',
                'Mask' => '255.255.255.0',
                'NumInventoried' => 0,
                'NumIdentified' => 0,
                'NumUnknown' => 1,
                'Name' => null,
            ),
            $subnets[0]->getArrayCopy()
        );
        $this->assertSame(
            array(
                'Address' => '198.51.100.0',
                'Mask' => '255.255.255.0',
                'NumInventoried' => 0,
                'NumIdentified' => 1,
                'NumUnknown' => 0,
                'Name' => null,
            ),
            $subnets[1]->getArrayCopy()
        );
        $this->assertSame(
            array(
                'Address' => '192.0.2.0',
                'Mask' => '255.255.255.128',
                'NumInventoried' => 1,
                'NumIdentified' => 1,
                'NumUnknown' => 1,
                'Name' => 'NAME',
            ),
            $subnets[2]->getArrayCopy()
        );
        $this->assertSame(
            array(
                'Address' => '192.0.2.0',
                'Mask' => '255.255.255.0',
                'NumInventoried' => 1,
                'NumIdentified' => 0,
                'NumUnknown' => 0,
                'Name' => null,
            ),
            $subnets[3]->getArrayCopy()
        );
    }

    public function getSubnetsOrderingProvider()
    {
        return array(
            array('NumInventoried', 'invalid', array(0, 0, 1, 1)), // becomes 'ASC'
            array('NumInventoried', 'asc', array(0, 0, 1, 1)),
            array('NumInventoried', 'desc', array(1, 1, 0, 0)),
            array('NumIdentified', 'asc', array(0, 0, 1, 1)),
            array('NumIdentified', 'desc', array(1, 1, 0, 0)),
            array('NumUnknown', 'asc', array(0, 0, 1, 1)),
            array('NumUnknown', 'desc', array(1, 1, 0, 0)),
            array(
                'CidrAddress',
                'asc',
                array(
                    '192.0.2.0/255.255.255.0',
                    '192.0.2.0/255.255.255.128',
                    '198.51.100.0/255.255.255.0',
                    '203.0.113.0/255.255.255.0',
                ),
            ),
            array(
                'CidrAddress',
                'desc',
                array(
                    '203.0.113.0/255.255.255.0',
                    '198.51.100.0/255.255.255.0',
                    '192.0.2.0/255.255.255.128',
                    '192.0.2.0/255.255.255.0',
                ),
            ),
        );
    }

    /**
     * @dataProvider getSubnetsOrderingProvider
     */
    public function testGetSubnetsOrdering($order, $direction, $values)
    {
        $model = $this->getModel();
        $subnets = $model->getSubnets($order, $direction);
        $this->assertInstanceOf('Laminas\Db\ResultSet\AbstractResultSet', $subnets);
        $subnets = iterator_to_array($subnets);
        $this->assertContainsOnlyInstancesOf('Model\Network\Subnet', $subnets);
        // To keep the data set simple, not all values are unique. Since the
        // order of rows with identical sort values is undefined, only the sort
        // column is tested.
        $this->assertSame(
            $values,
            array_column(
                array_map(
                    function ($object) {
                        $subnet = $object->getArrayCopy();
                        $subnet['CidrAddress'] = "$subnet[Address]/$subnet[Mask]";
                        return $subnet;
                    },
                    $subnets
                ),
                $order
            )
        );
    }

    public function testGetSubnetsNoOrdering()
    {
        $model = $this->getModel();
        $subnets = $model->getSubnets();
        $this->assertInstanceOf('Laminas\Db\ResultSet\AbstractResultSet', $subnets);
        $subnets = iterator_to_array($subnets);
        $this->assertCount(4, $subnets);
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
        /** @var MockObject|IpNetworkAddress */
        $validator = $this->createMock(IpNetworkAddress::class);
        $validator->expects($this->once())->method('isValid')->with("$address/$mask")->willReturn(true);

        $model = new SubnetManager(
            static::$serviceManager->get(Subnets::class),
            $validator
        );
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

    public function testGetSubnetInvalidArguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message');

        /** @var MockObject|IpNetworkAddress */
        $validator = $this->createMock(IpNetworkAddress::class);
        $validator->expects($this->once())->method('isValid')->with('address/mask')->willReturn(false);
        $validator->method('getMessages')->willReturn(['message']);

        $model = new SubnetManager(
            static::$serviceManager->get(Subnets::class),
            $validator
        );
        $model->getSubnet('address', 'mask');
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
        /** @var MockObject|IpNetworkAddress */
        $validator = $this->createMock(IpNetworkAddress::class);
        $validator->expects($this->once())->method('isValid')->with("$address/$mask")->willReturn(true);

        $model = new SubnetManager(
            static::$serviceManager->get(Subnets::class),
            $validator
        );
        $model->saveSubnet($address, $mask, $name, $dataSet);
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('subnet'),
            $this->getConnection()->createQueryTable(
                'subnet',
                'SELECT netid, mask, name FROM subnet ORDER BY netid, mask'
            )
        );
    }

    public function testSaveSubnetInvalidArguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message');

        /** @var MockObject|IpNetworkAddress */
        $validator = $this->createMock(IpNetworkAddress::class);
        $validator->expects($this->once())->method('isValid')->with('address/mask')->willReturn(false);
        $validator->method('getMessages')->willReturn(['message']);

        $model = new SubnetManager(
            static::$serviceManager->get(Subnets::class),
            $validator
        );
        $model->saveSubnet('address', 'mask', null);
    }
}
