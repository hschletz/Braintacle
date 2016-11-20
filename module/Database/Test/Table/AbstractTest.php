<?php
/**
 * Base class for table interface tests
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

namespace Database\Test\Table;

/**
 * Base class for table interface tests
 *
 * The table, class and fixture are automatically set up and the service is
 * automatically tested.
 */
abstract class AbstractTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * Service manager
     * @var \Zend\ServiceManager\ServiceManager
     */
    public static $serviceManager;

    /**
     * Table class, provided by setUpBeforeClass();
     * @var \Database\AbstractTable
     */
    protected static $_table;

    /**
     * Connection used by DbUnit
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    private $_db;

    /**
     * Provide table class and create table
     */
    public static function setUpBeforeClass()
    {
        static::$_table = static::$serviceManager->get(static::_getClass());
        static::$_table->setSchema(true);
        parent::setUpBeforeClass();
    }

    /**
     * Get connection for DbUnit
     *
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (!$this->_db) {
            $pdo = static::$_table->getAdapter()->getDriver()->getConnection()->getResource();
            $this->_db = $this->createDefaultDBConnection($pdo, ':memory:');
        }
        return $this->_db;
    }
 
    /**
     * Set up fixture from data/Test/Classname.yaml
     *
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->_loadDataSet();
    }

    /**
     * Load dataset from data/Test/Classname[/$testName].yaml
     *
     * @param string $testName Test name. If NULL, the fixture dataset for the test class is loaded.
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function _loadDataSet($testName = null)
    {
        $class = $this->_getClass();
        $class = substr($class, strrpos($class, '\\') + 1); // Remove namespace
        $file = "data/Test/$class";
        if ($testName) {
            $file .= "/$testName";
        }
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            \Database\Module::getPath("$file.yaml")
        );
    }

    /**
     * Get the table class name, derived from the test class name
     *
     * @return string
     */
    protected static function _getClass()
    {
        // Derive table class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_called_class()), 0, -4);
    }

    /**
     * Test if the class is properly registered with the service manager
     */
    public function testInterface()
    {
        $this->assertInstanceOf('Database\AbstractTable', static::$_table);
    }
}
