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
        'AudioDevices',
        'Printers',
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
            array('AudioDevice', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Printer', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('Printer', null, 'Port', 'desc', array('name1', 'name2'), 'Name'),
            array('Printer', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('Printer', array('Client' => 2), null, null, array('name2'), 'Name'),
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
            $table = \Library\Application::getService("Database\\Table\\$table")->table;
            $dataSet->addTable($table, "SELECT hardware_id FROM $table");
        }
        $this->assertDataSetsEqual($this->_loadDataSet('DeleteItems'), $dataSet);
    }
}
