<?php

/**
 * Base class for table interface tests
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

namespace Database\Test\Table;

use PHPUnit\DbUnit\Database\Connection;

/**
 * Base class for table interface tests
 *
 * The table, class and fixture are automatically set up and the service is
 * automatically tested.
 */
abstract class AbstractTest extends \PHPUnit\DbUnit\TestCase
{
    /**
     * Service manager
     * @var \Laminas\ServiceManager\ServiceManager
     */
    public static $serviceManager;

    /**
     * Table class, provided by setUpBeforeClass();
     * @var \Database\AbstractTable
     */
    protected static $_table;

    /**
     * Connection used by DbUnit
     */
    private Connection $_db;

    /**
     * Provide table class and create table
     */
    public static function setUpBeforeClass(): void
    {
        static::$_table = static::$serviceManager->get(static::getClass());
        static::$_table->updateSchema(true);
        parent::setUpBeforeClass();
    }

    /**
     * Get connection for DbUnit
     */
    public function getConnection(): Connection
    {
        if (!isset($this->_db)) {
            $pdo = static::$_table->getAdapter()->getDriver()->getConnection()->getResource();
            $this->_db = $this->createDefaultDBConnection($pdo, ':memory:');
        }
        return $this->_db;
    }

    /**
     * Set up fixture from data/Test/Classname.yaml
     *
     * @return \PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        return $this->loadDataSet();
    }

    /**
     * Load dataset from data/Test/Classname[/$testName].yaml
     *
     * @param string $testName Test name. If NULL, the fixture dataset for the test class is loaded.
     * @return \PHPUnit\DbUnit\DataSet\IDataSet
     */
    protected function loadDataSet($testName = null)
    {
        $class = $this->getClass();
        $class = substr($class, strrpos($class, '\\') + 1); // Remove namespace
        $file = "data/Test/$class";
        if ($testName) {
            $file .= "/$testName";
        }
        return new \PHPUnit\DbUnit\DataSet\YamlDataSet(
            \Database\Module::getPath("$file.yaml")
        );
    }

    /**
     * Get the table class name, derived from the test class name
     *
     * @return string
     */
    protected static function getClass()
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
