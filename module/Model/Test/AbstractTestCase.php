<?php

/**
 * Base class for model tests
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Database\DatabaseFactory;
use Laminas\Db\Adapter\Adapter;
use Laminas\Di\Container\ServiceManager\AutowireFactory;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Noop as NoopWriter;
use Laminas\ServiceManager\ServiceManager;
use Library\Application;
use Nada\Database\AbstractDatabase;
use Nada\Factory;
use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\TestCase;
use PHPUnit\Framework\Attributes\Before;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Base class for model tests
 *
 * Tables that are given in $_tables are automatically set up, the fixture is
 * loaded and the service is automatically tested.
 */
abstract class AbstractTestCase extends TestCase
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

    private static Adapter $adapter;

    private static array $serviceManagerConfig;
    protected static ServiceManager $serviceManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$adapter = new Adapter(
            json_decode(
                getenv('BRAINTACLE_TEST_DATABASE'),
                true
            )
        );

        // Extend module-generated service manager config with required entries.
        $config = Application::init('Model')->getServiceManager()->get('config')['service_manager'];
        $config['abstract_factories'][] = AutowireFactory::class;
        $config['services'][AbstractDatabase::class] = (new DatabaseFactory(new Factory(), static::$adapter))();
        $config['services'][Adapter::class] = static::$adapter;
        $config['services'][LoggerInterface::class] = new PsrLoggerAdapter(
            new Logger(['writers' => [['name' => NoopWriter::class]]])
        );
        // Store config for creation of temporary service manager instances.
        static::$serviceManagerConfig = $config;

        // Create necessary tables.
        $serviceManager = static::createServiceManager();
        foreach (static::$_tables as $table) {
            $serviceManager->get("Database\Table\\$table")->updateSchema(true);
        }
    }

    #[Before]
    public function setupServiceManager(): void
    {
        // Set up a clean temporary service manager instance to be used by
        // tests, which can inject their own service mocks without interfering
        // with other tests.
        static::$serviceManager = $this->createServiceManager();
    }

    /**
     * Create new service manager instance.
     *
     * The instance is built from the stored config, without any leftover
     * service instances from other tests.
     */
    protected static function createServiceManager(): ServiceManager
    {
        $serviceManager = new ServiceManager(static::$serviceManagerConfig);
        $serviceManager->setService('config', static::$serviceManagerConfig);
        $serviceManager->setService(ContainerInterface::class, $serviceManager);

        return $serviceManager;
    }

    /**
     * Get connection for DbUnit
     */
    public function getConnection(): Connection
    {
        if (!isset($this->_db)) {
            $pdo = static::$adapter->getDriver()->getConnection()->getResource();
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
        switch (static::$adapter->getPlatform()->getName()) {
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
     * @return object Model instance
     * @deprecated Create instance by constructor, by pulling from the container, or as partial mock
     */
    protected function getModel()
    {
        return static::createServiceManager()->build($this->getClass());
    }

    /**
     * Test if the class is properly registered with the service manager
     */
    public function testInterface()
    {
        $this->assertIsObject($this->getModel());
    }
}
