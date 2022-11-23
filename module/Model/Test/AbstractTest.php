<?php

/**
 * Base class for model tests
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

namespace Model\Test;

use PHPUnit\DbUnit\Database\Connection;

/**
 * Base class for model tests
 *
 * Tables that are given in $_tables are automatically set up, the fixture is
 * loaded and the service is automatically tested.
 */
abstract class AbstractTest extends \PHPUnit\DbUnit\TestCase
{
    /**
     * Array of tables to set up (table class names without Database\Table prefix)
     * @var string[]
     */
    protected static $_tables = array();

    /**
     * Connection used by DbUnit
     */
    private Connection $_db;

    /**
     * Service manager
     * @var \Laminas\ServiceManager\ServiceManager
     */
    public static $serviceManager;

    public static function setUpBeforeClass(): void
    {
        foreach (static::$_tables as $table) {
            static::$serviceManager->get("Database\Table\\$table")->updateSchema(true);
        }
        parent::setUpBeforeClass();
    }

    /**
     * Get connection for DbUnit
     */
    public function getConnection(): Connection
    {
        if (!isset($this->_db)) {
            $pdo = static::$serviceManager->get('Db')->getDriver()->getConnection()->getResource();
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
        $class = substr($class, strpos($class, '\\')); // Remove 'Model' prefix
        $file = str_replace('\\', '/', "data/Test$class");
        if ($testName) {
            $file .= "/$testName";
        }
        return new \PHPUnit\DbUnit\DataSet\YamlDataSet(
            \Model\Module::getPath("$file.yaml")
        );
    }

    /**
     * Wrap dataset with platform specific boolean emulation for result comparison
     *
     * When comparing database query results with a dataset, boolean columns
     * cannot be compared directly due to platform-specific implementations.
     * This method returns a wrapper that replaces the given values for
     * TRUE/FALSE with their platform-specific counterparts.
     *
     * Due to the ReplacementDataSet implementation, real booleans cannot be
     * used in the source dataset.
     *
     * @param \PHPUnit\DbUnit\DataSet\IDataSet $dataSet Dataset to wrap
     * @param mixed $falseValue FALSE value used in $dataset
     * @param mixed $trueValue TRUE value used in $dataset
     * @return \PHPUnit\DbUnit\DataSet\ReplacementDataSet
     */
    protected function getBooleanDataSetWrapper(
        \PHPUnit\DbUnit\DataSet\IDataSet $dataSet,
        $falseValue,
        $trueValue
    ) {
        switch (static::$serviceManager->get('Db')->getPlatform()->getName()) {
            case 'MySQL':
                $falseReplacement = 0;
                $trueReplacement = 1;
                break;
            case 'SQLite':
                $falseReplacement = '0';
                $trueReplacement = '1';
                break;
            default:
                $falseReplacement = false;
                $trueReplacement = true;
        }
        return new \PHPUnit\DbUnit\DataSet\ReplacementDataSet(
            $dataSet,
            array($falseValue => $falseReplacement, $trueValue => $trueReplacement)
        );
    }

    /**
     * Get the model class name, derived from the test class name
     *
     * @return string
     */
    protected function getClass()
    {
        // Derive model class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_class($this)), 0, -4);
    }

    /**
     * Get new model instance via service manager
     *
     * This method allows temporarily overriding services with manually supplied
     * instances. This is useful for injecting mock objects which will be passed
     * to the model's constructor by a factory. A clone of the service manager is
     * used to avoid interference with other tests.
     *
     * @param array $overrideServices Optional associative array (name => instance) with services to override
     * @return object Model instance
     * @deprecated Create instance by constructor, by pulling from the container, or as partial mock
     */
    protected function getModel(array $overrideServices = array())
    {
        if (empty($overrideServices)) {
            $serviceManager = static::$serviceManager;
        } else {
            // Create temporary service manager with identical configuration.
            $config = static::$serviceManager->get('config');
            $serviceManager = new \Laminas\ServiceManager\ServiceManager($config['service_manager']);
            // Clone 'config' service
            $serviceManager->setService('config', $config);
            // If not explicitly overridden, copy database services to avoid
            // expensive reconnect or table setup which has already been done.
            if (!isset($overrideServices['Db'])) {
                $serviceManager->setService('Db', static::$serviceManager->get('Db'));
            }
            if (!isset($overrideServices['Database\Nada'])) {
                $serviceManager->setService('Database\Nada', static::$serviceManager->get('Database\Nada'));
            }
            // Override specified services
            foreach ($overrideServices as $name => $service) {
                $serviceManager->setService($name, $service);
            }
        }
        // Always build a new instance.
        return $serviceManager->build($this->getClass());
    }

    /**
     * Test if the class is properly registered with the service manager
     */
    public function testInterface()
    {
        $this->assertIsObject($this->getModel());
    }
}
