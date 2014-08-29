<?php
/**
 * Tests for AbstractTable helper methods
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

namespace Database\Test\Table;

/**
 * Tests for AbstractTable helper methods
 */
class AbstractTableTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * Table class
     * @var \Database\AbstractTable
     */
    protected $_table;

    /**
     * Connection used by DbUnit
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    private $_db;

    public static function setUpBeforeClass()
    {
        $database = \Library\Application::getService('Database\Nada');
        $database->createTable(
            'test1',
            array(
                array('name' => 'col1', 'type' => 'varchar', 'length' => 10, 'notnull' => true),
                array('name' => 'col2', 'type' => 'varchar', 'length' => 10, 'notnull' => true),
            ),
            'col1'
        );
        $database->createTable(
            'test2',
            array(
                array('name' => 'col1', 'type' => 'varchar', 'length' => 10, 'notnull' => true),
            ),
            'col1'
        );
        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        $this->_table = $this->getMockBuilder('Database\AbstractTable')
                             ->disableOriginalConstructor()
                             ->getMockForAbstractClass();
        $adapter = new \ReflectionProperty('Database\AbstractTable', 'adapter');
        $adapter->setAccessible(true);
        $adapter->setValue($this->_table, \Library\Application::getService('Db'));
        return parent::setUp();
    }

    public function getConnection()
    {
        if (!$this->_db) {
            $pdo = \Library\Application::getService('Db')->getDriver()->getConnection()->getResource();
            $this->_db = $this->createDefaultDBConnection($pdo, ':memory:');
        }
        return $this->_db;
    }
 
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            \Database\Module::getPath("data/Test/AbstractTable.yaml")
        );
    }

    public function testFetchColWithData()
    {
        $table = new \ReflectionProperty('Database\AbstractTable', 'table');
        $table->setAccessible(true);
        $table->setValue($this->_table, 'test1');
        $this->_table->initialize();
        $this->assertEquals(array('col2a', 'col2b'), $this->_table->fetchCol('col2'));
    }

    public function testFetchColWithEmptyTable()
    {
        $table = new \ReflectionProperty('Database\AbstractTable', 'table');
        $table->setAccessible(true);
        $table->setValue($this->_table, 'test2');
        $this->_table->initialize();
        $this->assertSame(array(), $this->_table->fetchCol('col1'));
    }
}
