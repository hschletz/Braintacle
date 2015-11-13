<?php
/**
 * Schema management class
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

Namespace Database;

/**
 * Schema management class
 *
 * This class contains all functionality to manage the database schema and to
 * initialize and migrate data.
 *
 * @codeCoverageIgnore
 */
class SchemaManager
{
    /**
     * Legacy schema manager passed to the constructor
     * @var \Braintacle_SchemaManager
     */
    protected $_legacySchemaManager;

    /**
     * Database adapter
     * @var \Zend\Db\Adapter
     */
    protected $_db;

    /**
     * NADA interface
     * @var \Nada_Database
     */
     protected $_nada;

    /**
     * Constructor
     *
     * @param \Braintacle_SchemaManager $legacySchemaManager Legacy schema manager
     */
    function __construct(\Braintacle_SchemaManager $legacySchemaManager)
    {
        $this->_legacySchemaManager = $legacySchemaManager;
        $this->_db = \Library\Application::getService('Db');
        $this->_nada = \Library\Application::getService('Database\Nada');
    }

    /**
     * Update database automatically
     *
     * This is the simplest way to update the database. It performs all
     * necessary steps to update the database schema and migrate data.
     */
    public function updateAll()
    {
        $this->updateTables();
        $this->_legacySchemaManager->updateAll();
    }

    /**
     * Create/update all tables
     *
     * This method iterates over all JSON schema files in ./data, instantiates
     * table objects of the same name for each file and calls their setSchema()
     * method.
     */
    public function updateTables()
    {
        $glob = new \GlobIterator(Module::getPath('data/Tables') . '/*.json');
        foreach ($glob as $fileinfo) {
            $tableClass = $fileinfo->getBaseName('.json');
            $table = \Library\Application::getService('Database\Table\\' . $tableClass);
            $table->setSchema();
        }
        // Views need manual invocation.
        \Library\Application::getService('Database\Table\Clients')->setSchema();
        \Library\Application::getService('Database\Table\PackageDownloadInfo')->setSchema();
        \Library\Application::getService('Database\Table\WindowsInstallations')->setSchema();

        $logger = \Library\Application::getService('Library\Logger');

        // Server tables have no table class
        $glob = new \GlobIterator(Module::getPath('data/Tables/Server') . '/*.json');
        foreach ($glob as $fileinfo) {
            self::setSchema(
                $logger,
                \Zend\Config\Factory::fromFile($fileinfo->getPathname()),
                $this->_nada
            );
        }

        // SNMP tables have no table class
        $glob = new \GlobIterator(Module::getPath('data/Tables/Snmp') . '/*.json');
        foreach ($glob as $fileinfo) {
            self::setSchema(
                $logger,
                \Zend\Config\Factory::fromFile($fileinfo->getPathname()),
                $this->_nada
            );
        }
    }

    /**
     * Create or update table according to schema
     *
     * @param \Zend\Log\Logger $logger Logger instance
     * @param array $schema Parsed table schema
     * @param \Nada_Database $database Database object
     */
    public static function setSchema($logger, $schema, $database)
    {
        $tableName = $schema['name'];
        if (in_array($tableName, $database->getTableNames())) {
            // Table exists
            // Update table and column comments
            $table = $database->getTable($tableName);
            if ($schema['comment'] != $table->getComment()) {
                $table->setComment($schema['comment']);
            }
            $columns = $table->getColumns();
            foreach ($schema['columns'] as $column) {
                if (isset($columns[$column['name']])) {
                    // Column exists. Set comment.
                    $columnObj = $table->getColumn($column['name']);
                    $columnObj->setComment($column['comment']);
                    // Change datatype if different.
                    if (
                        $columnObj->getDatatype() != $column['type'] or
                        $columnObj->getLength() != $column['length']
                    ) {
                        $logger->info(
                            "Setting column $tableName.$column[name] type to $column[type]($column[length])..."
                        );
                        $columnObj->setDatatype($column['type'], $column['length']);
                        $logger->info('done.');
                    }
                    // Change constraints if different.
                    if ($columnObj->getNotNull() != $column['notnull']) {
                        $logger->info(
                            ($column['notnull'] ? 'Setting' : 'Removing') .
                            " NOT NULL constraint on column $tableName.$column[name]..."
                        );
                        $columnObj->setNotNull($column['notnull']);
                        $logger->info('done.');
                    }
                    // Change default if different.
                    if (
                        // Since SQL types cannot be completely mapped to PHP
                        // types, a loose comparision is required, but changes
                        // to/from NULL must be taken into account.
                        $columnObj->getDefault() === null and $column['default'] !== null or
                        $columnObj->getDefault() !== null and $column['default'] === null or
                        $columnObj->getDefault() != $column['default']
                    ) {
                        $logger->info(
                            sprintf(
                                "Setting default value of column $tableName.$column[name] to %s...",
                                ($column['default'] === null) ? 'NULL' : "'$column[default]'"
                            )
                        );
                        $columnObj->setDefault($column['default']);
                        $logger->info('done.');
                    }
                } else {
                    $logger->info("Creating column $tableName.$column[name]...");
                    $table->addColumnObject($database->createColumnFromArray($column));
                    $logger->info('done.');
                }
            }

            // Check for altered PK definition
            $primaryKey = $table->getPrimaryKey();
            if ($primaryKey) {
                foreach ($primaryKey as &$column) {
                    $column = $column->getName();
                }
                unset($column);
            } else {
                $primaryKey = array();
            }
            if ($schema['primary_key'] != $primaryKey) {
                $logger->info(
                    sprintf(
                        'Changing PK of %s from (%s) to (%s)...',
                        $tableName,
                        implode(', ', $primaryKey),
                        implode(', ', $schema['primary_key'])
                    )
                );
                $table->setPrimaryKey($schema['primary_key']);
                $logger->info('done.');
            }
        } else {
            // Table does not exist, create it
            $logger->info("Creating table '$tableName'...");
            $table = $database->createTable($tableName, $schema['columns'], $schema['primary_key']);
            $table->setComment($schema['comment']);
            if ($database->isMySql()) {
                $table->setEngine($schema['mysql']['engine']);
                $table->setCharset('utf8');
            }
            $logger->info('done.');
        }

        // Create missing indexes. Ignore name for comparision with existing indexes.
        if (isset($schema['indexes'])) {
            foreach ($schema['indexes'] as $index) {
                if (!$table->hasIndex($index['columns'], $index['unique'])) {
                    $logger->info("Creating index '$index[name]'...");
                    $table->createIndex($index['name'], $index['columns'], $index['unique']);
                    $logger->info('done.');
                }
            }
        }
    }
}
