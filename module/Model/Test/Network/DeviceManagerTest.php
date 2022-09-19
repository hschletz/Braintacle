<?php

/**
 * Tests for Model\Network\DeviceManager
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

/**
 * Tests for Model\Network\DeviceManager
 */
class DeviceManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array(
        'NetworkDevicesIdentified',
        'NetworkDevicesScanned',
        'NetworkDeviceTypes',
        'NetworkInterfaces',
    );

    public function testGetDevicesNoFilterOrderByVendorDesc()
    {
        $model = $this->getModel();
        $devices = iterator_to_array($model->getDevices(array(), 'Vendor', 'desc'));
        $this->assertCount(6, $devices);
        $this->assertContainsOnlyInstancesOf('Model\Network\Device', $devices);
        $allDevices = $this->loadDataSet()->getTable('netmap');
        // Ordered by MAC address (best approximation for vendor)
        $this->assertEquals($allDevices->getRow(8)['mac'], $devices[0]['MacAddress']);
        $this->assertEquals($allDevices->getRow(6)['mac'], $devices[1]['MacAddress']);
        $this->assertEquals($allDevices->getRow(5)['mac'], $devices[2]['MacAddress']);
        $this->assertEquals($allDevices->getRow(4)['mac'], $devices[3]['MacAddress']);
        $this->assertEquals($allDevices->getRow(2)['mac'], $devices[4]['MacAddress']);
        $this->assertEquals($allDevices->getRow(0)['mac'], $devices[5]['MacAddress']);
    }

    public function testGetDevicesFilterByNetwork()
    {
        $model = $this->getModel();
        $devices = iterator_to_array(
            $model->getDevices(
                array('Subnet' => '198.51.100.0', 'Mask' => '255.255.255.0'),
                'Hostname'
            )
        );
        $this->assertCount(2, $devices);
        $this->assertContainsOnlyInstancesOf('Model\Network\Device', $devices);
        $allDevices = $this->loadDataSet()->getTable('netmap');
        $this->assertEquals($allDevices->getRow(4)['mac'], $devices[0]['MacAddress']);
        $this->assertEquals($allDevices->getRow(6)['mac'], $devices[1]['MacAddress']);
    }

    public function testGetDevicesFilterByType()
    {
        $model = $this->getModel();
        $devices = iterator_to_array(
            $model->getDevices(array('Type' => 'present, inventoried interfaces'), 'Hostname')
        );
        $this->assertCount(2, $devices);
        $this->assertContainsOnlyInstancesOf('Model\Network\Device', $devices);
        $allDevices = $this->loadDataSet()->getTable('netmap');
        $this->assertEquals($allDevices->getRow(0)['mac'], $devices[0]['MacAddress']);
        $this->assertEquals($allDevices->getRow(2)['mac'], $devices[1]['MacAddress']);
        $this->assertEquals('device1', $devices[0]['Description']);
        $this->assertEquals('device3', $devices[1]['Description']);
    }

    public function testGetDevicesFilterByIdentifiedTrue()
    {
        $model = $this->getModel();
        $devices = iterator_to_array($model->getDevices(array('Identified' => true), 'Hostname'));
        $this->assertCount(5, $devices);
        $this->assertContainsOnlyInstancesOf('Model\Network\Device', $devices);
        $allDevices = $this->loadDataSet()->getTable('netmap');
        $this->assertEquals($allDevices->getRow(0)['mac'], $devices[0]['MacAddress']);
        $this->assertEquals($allDevices->getRow(2)['mac'], $devices[1]['MacAddress']);
        $this->assertEquals($allDevices->getRow(4)['mac'], $devices[2]['MacAddress']);
        $this->assertEquals($allDevices->getRow(5)['mac'], $devices[3]['MacAddress']);
        $this->assertEquals($allDevices->getRow(6)['mac'], $devices[4]['MacAddress']);
        $this->assertEquals('device1', $devices[0]['Description']);
        $this->assertEquals('device3', $devices[1]['Description']);
        // Extra properties from other devices should be OK too
    }

    public function testGetDevicesFilterByIdentifiedFalse()
    {
        $model = $this->getModel();
        $devices = iterator_to_array($model->getDevices(array('Identified' => false), 'Hostname'));
        $this->assertCount(1, $devices);
        $this->assertContainsOnlyInstancesOf('Model\Network\Device', $devices);
        $allDevices = $this->loadDataSet()->getTable('netmap');
        $this->assertEquals($allDevices->getRow(8)['mac'], $devices[0]['MacAddress']);
    }

    public function testGetDeviceByMacAddressIdentified()
    {
        $model = $this->getModel();
        $device = $model->getDevice(new \Library\MacAddress('00:00:5E:00:53:03'));
        $this->assertInstanceOf('Model\Network\Device', $device);
        $this->assertEquals(
            array (
                'IpAddress' => '192.0.2.3',
                'MacAddress' => '00:00:5E:00:53:03',
                'Hostname' => 'name3',
                'DiscoveryDate' => new \DateTime('2014-12-28 17:40:00'),
                'Description' => 'device3',
                'Type' => 'present, inventoried interfaces',
            ),
            $device->getArrayCopy()
        );
    }

    public function testGetDeviceByStringNotIdentified()
    {
        $model = $this->getModel();
        $device = $model->getDevice('00:00:5E:00:53:09');
        $this->assertInstanceOf('Model\Network\Device', $device);
        $this->assertEquals(
            array (
                'IpAddress' => '192.0.2.9',
                'MacAddress' => '00:00:5E:00:53:09',
                'Hostname' => 'name9',
                'DiscoveryDate' => new \DateTime('2014-12-28 17:40:00'),
                'Description' => null,
                'Type' => null,
            ),
            $device->getArrayCopy()
        );
    }

    public function testGetDeviceUnknownAddress()
    {
        $this->expectException('Model\Network\RuntimeException');
        $this->expectExceptionMessage('Unknown MAC address: 00:00:5E:00:53:00');
        $model = $this->getModel();
        $device = $model->getDevice('00:00:5E:00:53:00');
    }

    public function saveDeviceProvider()
    {
        return array(
            array('00:00:5E:00:53:07', 'SaveDeviceUpdate'),
            array('00:00:5E:00:53:08', 'SaveDeviceInsert'),
        );
    }

    /**
     * @dataProvider saveDeviceProvider
     */
    public function testSaveDevice($macAddress, $dataSet)
    {
        $model = $this->getModel();
        $model->saveDevice(new \Library\MacAddress($macAddress), 'new type', 'new description');
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('network_devices'),
            $this->getConnection()->createQueryTable(
                'network_devices',
                'SELECT macaddr, description, type FROM network_devices ORDER BY macaddr'
            )
        );
    }

    public function testDeleteDeviceByMacAddress()
    {
        $model = $this->getModel();
        $model->deleteDevice(new \Library\MacAddress('00:00:5E:00:53:01'));
        $dataSet = $this->loadDataSet('DeleteDevice');
        $connection = $this->getConnection();
        $this->assertTablesEqual(
            $dataSet->getTable('network_devices'),
            $connection->createQueryTable('network_devices', 'SELECT macaddr FROM network_devices')
        );
        $this->assertTablesEqual(
            $dataSet->getTable('netmap'),
            $connection->createQueryTable('netmap', 'SELECT mac FROM netmap ORDER BY mac')
        );
    }

    public function testDeleteDeviceByString()
    {
        $model = $this->getModel();
        $model->deleteDevice('00:00:5E:00:53:01');
        $dataSet = $this->loadDataSet('DeleteDevice');
        $connection = $this->getConnection();
        $this->assertTablesEqual(
            $dataSet->getTable('network_devices'),
            $connection->createQueryTable('network_devices', 'SELECT macaddr FROM network_devices')
        );
        $this->assertTablesEqual(
            $dataSet->getTable('netmap'),
            $connection->createQueryTable('netmap', 'SELECT mac FROM netmap ORDER BY mac')
        );
    }

    public function testGetTypes()
    {
        $model = $this->getModel();
        $this->assertEquals(
            array(
                'not present, inventoried interfaces',
                'not present, no inventoried interfaces',
                'present, inventoried interfaces',
                'present, no inventoried interfaces',
            ),
            $model->getTypes()
        );
    }

    public function testGetTypeCounts()
    {
        $model = $this->getModel();
        $this->assertEquals(
            array(
                'not present, inventoried interfaces' => '0',
                'not present, no inventoried interfaces' => '0',
                'present, inventoried interfaces' => '2',
                'present, no inventoried interfaces' => '3',
            ),
            $model->getTypeCounts()
        );
    }

    public function testAddType()
    {
        $model = $this->getModel();
        $model->addType('new type');
        $this->assertTablesEqual(
            $this->loadDataSet('AddType')->getTable('devicetype'),
            $this->getConnection()->createQueryTable(
                'devicetype',
                'SELECT name FROM devicetype ORDER BY name'
            )
        );
    }

    public function testAddTypeExists()
    {
        $model = $this->getModel();
        try {
            $model->addType('present, inventoried interfaces');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type already exists: present, inventoried interfaces',
                $e->getMessage()
            );
            $this->assertTablesEqual(
                $this->loadDataSet()->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
        }
    }

    public function testRenameType()
    {
        $model = $this->getModel();
        $model->renameType('present, inventoried interfaces', 'new type');
        $dataSet = $this->loadDataSet('RenameType');
        $this->assertTablesEqual(
            $dataSet->getTable('devicetype'),
            $this->getConnection()->createQueryTable(
                'devicetype',
                'SELECT name FROM devicetype ORDER BY name'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('network_devices'),
            $this->getConnection()->createQueryTable(
                'network_devices',
                'SELECT macaddr, type FROM network_devices ORDER BY macaddr'
            )
        );
    }

    public function testRenameTypeNewTypeExists()
    {
        $model = $this->getModel();
        try {
            $model->renameType('present, inventoried interfaces', 'present, inventoried interfaces');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type already exists: present, inventoried interfaces',
                $e->getMessage()
            );
            $dataSet = $this->loadDataSet();
            $this->assertTablesEqual(
                $dataSet->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable(
                    'network_devices',
                    'SELECT macaddr, description, type FROM network_devices'
                )
            );
        }
    }

    public function testRenameTypeOldTypeDoesNotExist()
    {
        $model = $this->getModel();
        try {
            $model->renameType('invalid', 'new type');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type does not exist: invalid',
                $e->getMessage()
            );
            $dataSet = $this->loadDataSet();
            $this->assertTablesEqual(
                $dataSet->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable(
                    'network_devices',
                    'SELECT macaddr, description, type FROM network_devices'
                )
            );
        }
    }

    public function testDeleteType()
    {
        $model = $this->getModel();
        $model->deleteType('not present, inventoried interfaces');
        $dataSet = $this->loadDataSet('DeleteType');
        $this->assertTablesEqual(
            $dataSet->getTable('devicetype'),
            $this->getConnection()->createQueryTable(
                'devicetype',
                'SELECT name FROM devicetype ORDER BY name'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('network_devices'),
            $this->getConnection()->createQueryTable('network_devices', 'SELECT macaddr, type FROM network_devices')
        );
    }

    public function testDeleteTypeInUse()
    {
        $model = $this->getModel();
        try {
            $model->deleteType('present, inventoried interfaces');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type still in use: present, inventoried interfaces',
                $e->getMessage()
            );
            $dataSet = $this->loadDataSet();
            $this->assertTablesEqual(
                $this->loadDataSet()->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable(
                    'network_devices',
                    'SELECT macaddr, description, type FROM network_devices'
                )
            );
        }
    }

    public function testDeleteTypeNotExists()
    {
        $model = $this->getModel();
        try {
            $model->deleteType('invalid');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type does not exist: invalid',
                $e->getMessage()
            );
            $dataSet = $this->loadDataSet();
            $this->assertTablesEqual(
                $this->loadDataSet()->getTable('devicetype'),
                $this->getConnection()->createQueryTable(
                    'devicetype',
                    'SELECT name FROM devicetype ORDER BY name'
                )
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable(
                    'network_devices',
                    'SELECT macaddr, description, type FROM network_devices'
                )
            );
        }
    }
}
