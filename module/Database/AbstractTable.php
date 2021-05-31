<?php

/**
 * Base class for table objects
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

namespace Database;

use Database\Schema\TableSchema;
use Iterator;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Library\Hydrator\Iterator\HydratingIteratorIterator;
use LogicException;

/**
 * Base class for table objects
 *
 * Table objects should be pulled from the service manager which provides the
 * Database\Table\ClassName services which will create and set up object
 * instances.
 */
abstract class AbstractTable extends \Laminas\Db\TableGateway\AbstractTableGateway
{
    /**
     * @var Connection|null
     */
    protected $connection;

    /**
     * Service manager
     * @var \Laminas\ServiceManager\ServiceLocatorInterface
     */
    protected $_serviceLocator;

    /**
     * Hydrator
     * @var \Laminas\Hydrator\AbstractHydrator
     */
    protected $_hydrator;

    /** @codeCoverageIgnore */
    public function __construct(ServiceLocatorInterface $serviceLocator, Connection $connection = null)
    {
        $this->connection = $connection ?: $serviceLocator->get(Connection::class);
        $this->_serviceLocator = $serviceLocator;
        if (!$this->table) {
            if (defined('static::TABLE')) {
                $this->table = static::TABLE;
            } else {
                // If not set explicitly, derive table name from class name.
                // Uppercase letters cause an underscore to be inserted, except
                // at the beginning of the string.
                $this->table = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $this->getClassName()));
            }
        }
        $this->adapter = $serviceLocator->get('Db');
    }

    /**
     * Get service locator.
     * @codeCoverageIgnore
     */
    public function getServiceLocator(): \Laminas\ServiceManager\ServiceLocatorInterface
    {
        return $this->_serviceLocator;
    }

    /**
     * Get hydrator suitable for bridging with model
     *
     * @return \Laminas\Hydrator\AbstractHydrator|null
     */
    public function getHydrator()
    {
        return $this->_hydrator;
    }

    /**
     * Wrap raw results in an iterator which returns hydrated objects.
     *
     * Object prototype is provided by {@see getPrototype()}.
     */
    public function getIterator(Iterator $data): Iterator
    {
        return new HydratingIteratorIterator($this->getHydrator(), $data, $this->getPrototype());
    }

    /**
     * Get prototype for {@see getIterator()}.
     *
     * @return object|string Prototype object instance or class name
     * @throws LogicException base implementation does not provide a prototype.
     */
    public function getPrototype()
    {
        throw new LogicException(get_class($this) . '::' . __FUNCTION__ . '() is not implemented');
    }

    /**
     * Get database connection object
     *
     * @return \Laminas\Db\Adapter\Driver\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->getAdapter()->getDriver()->getConnection();
    }

    /**
     * Helper method to get class name without namespace
     *
     * @return string Class name
     * @internal
     * @codeCoverageIgnore
     */
    protected function getClassName()
    {
        return substr(get_class($this), strrpos(get_class($this), '\\') + 1);
    }

    /**
     * Create or update table according to schema file
     *
     * The schema file is located in ./data/ClassName.json and contains all
     * information required to create or alter the table.
     *
     * @param bool $prune Drop obsolete columns
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        $schema = \Laminas\Config\Factory::fromFile(
            Module::getPath('data/Tables/' . $this->getClassName() . '.json')
        );

        $this->preSetSchema($schema, $prune);
        $this->setSchema($schema, $prune);
        $this->postSetSchema($schema, $prune);
    }

    /**
     * Hook to be called before creating/altering table schema
     *
     * @param array $schema Parsed table schema
     * @param bool $prune Drop obsolete columns
     *
     * @codeCoverageIgnore
     */
    protected function preSetSchema(array $schema, bool $prune): void
    {
    }

    /**
     * Create or update table
     *
     * The default implementation calls TableSchema::setSchema().
     *
     * @codeCoverageIgnore
     */
    protected function setSchema(array $schema, bool $prune): void
    {
        $this->_serviceLocator->get(TableSchema::class)->setSchema($schema, $prune);
    }

    /**
     * Hook to be called after creating/altering table schema
     *
     * @param array $schema Parsed table schema
     * @param bool $prune Drop obsolete columns
     *
     * @codeCoverageIgnore
     */
    protected function postSetSchema(array $schema, bool $prune): void
    {
    }

    /**
     * Fetch a single column as a flat array
     *
     * @param string $name Column name
     * @return array
     * @deprecated use Doctrine\Dbal methods directly
     */
    public function fetchCol($name)
    {
        $select = $this->getSql()->select();
        $select->columns(array($name), false);
        $resultSet = $this->selectWith($select);

        // Map column name to corresponding result key
        if ($resultSet instanceof \Laminas\Db\ResultSet\HydratingResultSet) {
            $hydrator = $resultSet->getHydrator();
            if ($hydrator instanceof \Laminas\Hydrator\AbstractHydrator) {
                $name = $hydrator->hydrateName($name);
            }
        }

        $col = array();
        foreach ($resultSet as $row) {
            $col[] = $row[$name];
        }
        return $col;
    }
}
