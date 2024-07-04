<?php

/**
 * Base class for table objects
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Db\Adapter\Adapter;
use Laminas\Hydrator\HydratorInterface;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Base class for table objects
 */
abstract class AbstractTable extends \Laminas\Db\TableGateway\AbstractTableGateway
{
    /**
     * Hydrator
     */
    protected HydratorInterface $_hydrator;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(protected ContainerInterface $container)
    {
        if (!$this->table) {
            // If not set explicitly, derive table name from class name.
            // Uppercase letters cause an underscore to be inserted, except at
            // the beginning of the string.
            $this->table = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $this->getClassName()));
        }
        $this->adapter = $container->get(Adapter::class);
        $this->initialize();
    }

    /**
     * @codeCoverageIgnore
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
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
        $logger = $this->container->get(LoggerInterface::class);
        $schema = \Laminas\Config\Factory::fromFile(
            Module::getPath('data/Tables/' . $this->getClassName() . '.json')
        );
        $database = $this->container->get(AbstractDatabase::class);

        $this->preSetSchema($logger, $schema, $database, $prune);
        $this->setSchema($logger, $schema, $database, $prune);
        $this->postSetSchema($logger, $schema, $database, $prune);
    }

    /**
     * Hook to be called before creating/altering table schema
     *
     * @param LoggerInterface $logger Logger instance
     * @param array $schema Parsed table schema
     * @param AbstractDatabase $database Database object
     * @param bool $prune Drop obsolete columns
     * @codeCoverageIgnore
     */
    protected function preSetSchema($logger, $schema, $database, $prune)
    {
    }

    /**
     * Create or update table
     *
     * The default implementation calls \Database\SchemaManager::setSchema().
     *
     * @param LoggerInterface $logger Logger instance
     * @param array $schema Parsed table schema
     * @param AbstractDatabase $database Database object
     * @param bool $prune Drop obsolete columns
     * @codeCoverageIgnore
     */
    protected function setSchema($logger, $schema, $database, $prune)
    {
        \Database\SchemaManager::setSchema(
            $logger,
            $schema,
            $database,
            static::getObsoleteColumns($logger, $schema, $database),
            $prune
        );
    }

    /**
     * Hook to be called after creating/altering table schema
     *
     * @param LoggerInterface $logger Logger instance
     * @param array $schema Parsed table schema
     * @param AbstractDatabase $database Database object
     * @param bool $prune Drop obsolete columns
     * @codeCoverageIgnore
     */
    protected function postSetSchema($logger, $schema, $database, $prune)
    {
    }

    /**
     * Get names of columns that are present in the current database but not in
     * the given schema
     *
     * @param LoggerInterface $logger Logger instance
     * @param array $schema Parsed table schema
     * @param AbstractDatabase $database Database object
     * @return string[]
     * @codeCoverageIgnore
     */
    public static function getObsoleteColumns($logger, $schema, $database)
    {
        // Table may not exist yet if it's just about to be created
        if (in_array($schema['name'], $database->getTableNames())) {
            $schemaColumns = array_column($schema['columns'], 'name');
            $tableColumns = array_keys($database->getTable($schema['name'])->getColumns());
            return array_diff($tableColumns, $schemaColumns);
        } else {
            return array();
        }
    }

    /**
     * Rename table.
     * @codeCoverageIgnore
     */
    protected function rename(
        LoggerInterface $logger,
        AbstractDatabase $database,
        string $oldName
    ): void {
        $logger->info("Renaming table $oldName to $this->table...");
        $database->renameTable($oldName, $this->table);
        $logger->info('done.');
    }

    /**
     * Drop a column if it exists
     *
     * @param LoggerInterface $logger Logger instance
     * @param AbstractDatabase $database Database object
     * @param string $column column name
     * @codeCoverageIgnore
     */
    protected function dropColumnIfExists($logger, $database, $column)
    {
        $tables = $database->getTables();
        if (isset($tables[$this->table])) {
            $table = $tables[$this->table];
            $columns = $table->getColumns();
            if (isset($columns[$column])) {
                $logger->notice("Dropping column $this->table.$column...");
                $table->dropColumn($column);
                $logger->notice('done.');
            }
        }
    }

    /**
     * Fetch a single column as a flat array
     *
     * @param string $name Column name
     * @return array
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
