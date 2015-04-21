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
    );

    public function testGetTableInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid item type: invalid');
        $this->_getModel()->getTable('invalid');
    }

    public function getItemsProvider()
    {
    }

    /**
     * @dataProvider getItemsProvider
     */
    public function testGetItems($type, $filters, $order, $direction, $result, $keyColumn)
    {
        $model = $this->_getModel();
        $items = $model->getItems($type, $filters, $order, $direction);
        $this->assertInstanceOf('Zend\Db\Resultset\AbstractResultset', $items);
        $this->assertEquals(
            $result,
            array_map(
                function($element) use ($keyColumn) {
                    return $element[$keyColumn];
                },
                iterator_to_array($items)
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
