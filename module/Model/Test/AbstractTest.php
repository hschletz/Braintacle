<?php
/**
 * Base class for model tests
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Test;

/**
 * Base class for model tests
 *
 * Tables that are given in $_tables are automatically set up, the fixture is
 * loaded and the service is automatically tested.
 */
abstract class AbstractTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * Array of tables to set up (table class names without Database\Table prefix)
     * @var string[]
     */
    protected $_tables;

    /**
     * Connection used by DbUnit
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    private $_db;

    /**
     * Set up model and tables
     */
    public function setUp()
    {
        foreach ($this->_tables as $table) {
            \Library\Application::getService("Database\Table\\$table")->setSchema();
        }
        parent::setUp();
    }

    /**
     * Get connection for DbUnit
     * 
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (!$this->_db) {
            $pdo = \Library\Application::getService('Db')->getDriver()->getConnection()->getResource();
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
    protected function _loadDataSet($testName=null)
    {
        $class = $this->_getClass();
        $class = substr($class, strrpos($class, '\\') + 1); // Remove namespace
        $file = "data/Test/$class";
        if ($testName) {
            $file .= "/$testName";
        }
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            \Model\Module::getPath("$file.yaml")
        );
    }

    /**
     * Get the model class name, derived from the test class name
     *
     * @return string
     */
    protected function _getClass()
    {
        // Derive model class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_class($this)), 0, -4);
    }

    /**
     * Get model instance from service manager
     *
     * This method allows temporarily overriding services with manually supplied
     * instances. This is useful for injecting mock objects which will be passed
     * to the model's constructor by a factory. The service manager is reset to
     * its default state after the model is retrieved so that the override does
     * not interfere with subsequent code execution.
     *
     * @param array $overrideServices Optional associative array (name => instance) with services to override
     * @return object Model instance
     */
    protected function _getModel(array $overrideServices=array())
    {
        $serviceManager = \Library\Application::getService('ServiceManager');
        $module = $serviceManager->get('ModuleManager')->getModule('Model');
        $config = new \Zend\ServiceManager\Config($module->getConfig()['service_manager']);

        if (!empty($overrideServices)) {
            // Reset SM config. This will force a new instance of our model to
            // be created with overriden services.
            $config->configureServiceManager($serviceManager);
            // Override specified services
            $serviceManager->setAllowOverride(true);
            foreach ($overrideServices as $name => $service) {
                $serviceManager->setService($name, $service);
            }
        }

        $model = $serviceManager->get($this->_getClass());

        if (!empty($overrideServices)) {
            // Reset SM config to discard all service overrides
            $config->configureServiceManager($serviceManager);
        }

        return $model;
    }

    /**
     * Test if the class is properly registered with the service manager
     */
    public function testInterface()
    {
        $this->assertInternalType('object', $this->_getModel());
    }
}
