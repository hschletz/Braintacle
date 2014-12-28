<?php
/**
 * Tests for Model\Network\DeviceManager
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
    protected static $_tables = array('NetworkDeviceTypes', 'NetworkDevicesIdentified', 'NetworkInterfaces');

    public function testGetTypeCounts()
    {
        $model = $this->_getModel();
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
        $model = $this->_getModel();
        $model->addType('new type');
        $this->assertTablesEqual(
            $this->_loadDataSet('AddType')->getTable('devicetype'),
            $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
        );
    }

    public function testAddTypeExists()
    {
        $model = $this->_getModel();
        try {
            $model->addType('present, inventoried interfaces');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type already exists: present, inventoried interfaces',
                $e->getMessage()
            );
            $this->assertTablesEqual(
                $this->_loadDataSet()->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
        }
    }

    public function testRenameType()
    {
        $model = $this->_getModel();
        $model->renameType('present, inventoried interfaces', 'new type');
        $dataSet = $this->_loadDataSet('RenameType');
        $this->assertTablesEqual(
            $dataSet->getTable('devicetype'),
            $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
        );
        $this->assertTablesEqual(
            $dataSet->getTable('network_devices'),
            $this->getConnection()->createQueryTable('network_devices', 'SELECT macaddr, type FROM network_devices')
        );
    }

    public function testRenameTypeNewTypeExists()
    {
        $model = $this->_getModel();
        try {
            $model->renameType('present, inventoried interfaces', 'present, inventoried interfaces');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type already exists: present, inventoried interfaces',
                $e->getMessage()
            );
            $dataSet = $this->_loadDataSet();
            $this->assertTablesEqual(
                $dataSet->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable('network_devices', 'SELECT macaddr, type FROM network_devices')
            );
        }
    }

    public function testRenameTypeOldTypeDoesNotExist()
    {
        $model = $this->_getModel();
        try {
            $model->renameType('invalid', 'new type');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type does not exist: invalid',
                $e->getMessage()
            );
            $dataSet = $this->_loadDataSet();
            $this->assertTablesEqual(
                $dataSet->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable('network_devices', 'SELECT macaddr, type FROM network_devices')
            );
        }
    }

    public function testDeleteType()
    {
        $model = $this->_getModel();
        $model->deleteType('not present, inventoried interfaces');
        $dataSet = $this->_loadDataSet('DeleteType');
        $this->assertTablesEqual(
            $dataSet->getTable('devicetype'),
            $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
        );
        $this->assertTablesEqual(
            $dataSet->getTable('network_devices'),
            $this->getConnection()->createQueryTable('network_devices', 'SELECT macaddr, type FROM network_devices')
        );
    }

    public function testDeleteTypeInUse()
    {
        $model = $this->_getModel();
        try {
            $model->deleteType('present, inventoried interfaces');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type still in use: present, inventoried interfaces',
                $e->getMessage()
            );
            $dataSet = $this->_loadDataSet();
            $this->assertTablesEqual(
                $this->_loadDataSet()->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable('network_devices', 'SELECT macaddr, type FROM network_devices')
            );
        }
    }

    public function testDeleteTypeNotExists()
    {
        $model = $this->_getModel();
        try {
            $model->deleteType('invalid');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals(
                'Network device type does not exist: invalid',
                $e->getMessage()
            );
            $dataSet = $this->_loadDataSet();
            $this->assertTablesEqual(
                $this->_loadDataSet()->getTable('devicetype'),
                $this->getConnection()->createQueryTable('devicetype', 'SELECT name FROM devicetype')
            );
            $this->assertTablesEqual(
                $dataSet->getTable('network_devices'),
                $this->getConnection()->createQueryTable('network_devices', 'SELECT macaddr, type FROM network_devices')
            );
        }
    }
}
