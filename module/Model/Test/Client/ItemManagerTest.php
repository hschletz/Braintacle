<?php
/**
 * Tests for Model\Client\ItemManager
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

namespace Model\Test\Client;

class ItemManagerTest extends \Model\Test\AbstractTest
{
    protected static $_tables = array(
        'ClientsAndGroups',
        'AudioDevices',
        'Controllers',
        'Displays',
        'DisplayControllers',
        'ExtensionSlots',
        'InputDevices',
        'MemorySlots',
        'Modems',
        'NetworkInterfaces',
        'Ports',
        'Printers',
        'VirtualMachines',
    );

    public function testGetTableInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid item type: invalid');
        $this->_getModel()->getTable('invalid');
    }

    public function getItemsProvider()
    {
        return array(
            array('AudioDevice', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('AudioDevice', null, 'Name', 'desc', array('name2', 'name1'), 'Name'),
            array('AudioDevice', null, null, 'something', array('name2', 'name1'), 'Name'),
            array('audiodevice', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Controller', null, 'id', 'desc', array('name2', 'name1'), 'Name'),
            array('Controller', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('controller', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Display', null, 'id', 'asc', array('name1', 'name2'), 'Manufacturer'),
            array('Display', null, 'Type', 'desc', array('name1', 'name2'), 'Manufacturer'),
            array('Display', null, null, 'something', array('name1', 'name2'), 'Manufacturer'),
            array('display', array('Client' => 2), null, null, array('name2'), 'Manufacturer'),
            array('DisplayController', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('DisplayController', null, 'Chipset', 'desc', array('name1', 'name2'), 'Name'),
            array('DisplayController', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('displaycontroller', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('ExtensionSlot', null, 'id', 'desc', array('name2', 'name1'), 'Name'),
            array('ExtensionSlot', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('extensionslot', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('InputDevice', null, 'id', 'asc', array('name1', 'name2'), 'Description'),
            array('InputDevice', null, 'Description', 'desc', array('name2', 'name1'), 'Description'),
            array('InputDevice', null, null, 'something', array('name2', 'name1'), 'Description'),
            array('inputdevice', array('Client' => 2), null, null, array('name2'), 'Description'),
            array('MemorySlot', null, 'id', 'asc', array('name1', 'name2'), 'Description'),
            array('MemorySlot', null, 'Description', 'desc', array('name2', 'name1'), 'Description'),
            array('MemorySlot', null, null, 'something', array('name2', 'name1'), 'Description'),
            array('memoryslot', array('Client' => 2), null, null, array('name2'), 'Description'),
            array('Modem', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('Modem', null, 'Name', 'desc', array('name2', 'name1'), 'Name'),
            array('Modem', null, null, 'something', array('name2', 'name1'), 'Name'),
            array('modem', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('NetworkInterface', null, 'id', 'asc', array('1', '0'), 'IsBlacklisted'),
            array('NetworkInterface', null, 'Status', 'desc', array('1', '0'), 'IsBlacklisted'),
            array('NetworkInterface', null, null, 'something', array('1', '0'), 'IsBlacklisted'),
            array('networkinterface', array('Client' => 2), null, null, array('0'), 'IsBlacklisted'),
            array('Port', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('Port', null, 'Type', 'desc', array('name1', 'name2'), 'Name'),
            array('Port', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('port', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Printer', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('Printer', null, 'Port', 'desc', array('name1', 'name2'), 'Name'),
            array('Printer', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('printer', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('VirtualMachine', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('VirtualMachine', null, 'Type', 'desc', array('name1', 'name2'), 'Name'),
            array('VirtualMachine', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('virtualmachine', array('Client' => 2), null, null, array('name2'), 'Name'),
        );
    }

    /**
     * @dataProvider getItemsProvider
     */
    public function testGetItems($type, $filters, $order, $direction, $result, $keyColumn)
    {
        $model = $this->_getModel();
        $items = $model->getItems($type, $filters, $order, $direction);
        $this->assertInstanceOf('Zend\Db\Resultset\AbstractResultset', $items);
        $items = iterator_to_array($items);
        $this->assertContainsOnlyInstancesOf("Model\\Client\\Item\\$type", $items);
        $this->assertEquals(
            $result,
            array_map(
                function($element) use ($keyColumn) {
                    return $element[$keyColumn];
                },
                $items
            )
        );
    }

    public function testDeleteItems()
    {
        $model = $this->_getModel();
        $model->deleteItems(1);
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        foreach (static::$_tables as $table) {
            if ($table == 'ClientsAndGroups') {
                continue;
            }
            $table = \Library\Application::getService("Database\\Table\\$table")->table;
            $dataSet->addTable($table, "SELECT hardware_id FROM $table");
        }
        $this->assertDataSetsEqual($this->_loadDataSet('DeleteItems'), $dataSet);
    }
}
