<?php

/**
 * Schema management class
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

use Database\Table\Clients;
use Database\Table\CustomFieldConfig;
use Database\Table\PackageDownloadInfo;
use Database\Table\Software;
use Database\Table\WindowsInstallations;
use Laminas\Db\Adapter\Adapter;
use Model\Config;
use Nada\Database\AbstractDatabase;
use Nada\Table\AbstractTable as NadaTable;
use Nada\Table\Mysql;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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
     * Latest version of data transformations that cannot be detected automatically
     */
    const SCHEMA_VERSION = 8;

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Update database automatically
     *
     * This is the simplest way to update the database. It performs all
     * necessary steps to update the database schema and migrate data.
     *
     * Database updates are wrapped in a transaction to prevent incomplete and
     * possibly inconsistent updates in case of an error. Transaction support
     * may be limited by the database.
     *
     * @param bool $prune Drop obsolete tables/columns
     */
    public function updateAll($prune)
    {
        $nada = $this->container->get(AbstractDatabase::class);
        $connection = $this->container->get(Adapter::class)->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $convertedTimestamps = $nada->convertTimestampColumns();
            if ($convertedTimestamps) {
                $this->container->get(LoggerInterface::class)->info(
                    sprintf(
                        '%d columns converted to %s.',
                        $convertedTimestamps,
                        $nada->getNativeDatatype(\Nada\Column\AbstractColumn::TYPE_TIMESTAMP)
                    )
                );
            }
            $this->updateTables($prune);
            $this->container->get(Config::class)->schemaVersion = self::SCHEMA_VERSION;
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }

    /**
     * Create/update all tables
     *
     * This method iterates over all JSON schema files in ./data, instantiates
     * table objects of the same name for each file and calls their
     * updateSchema() method.
     *
     * @param bool $prune Drop obsolete tables/columns
     */
    public function updateTables($prune)
    {
        $database = $this->container->get(AbstractDatabase::class);
        $handledTables = array();

        $glob = new \GlobIterator(Module::getPath('data/Tables') . '/*.json');
        foreach ($glob as $fileinfo) {
            $tableClass = $fileinfo->getBaseName('.json');
            $table = $this->container->get('Database\Table\\' . $tableClass);
            $table->updateSchema($prune);
            $handledTables[] = $table->table;
        }
        // Views need manual invocation.
        $this->container->get(Clients::class)->updateSchema();
        $this->container->get(PackageDownloadInfo::class)->updateSchema();
        $this->container->get(WindowsInstallations::class)->updateSchema();
        $this->container->get(Software::class)->updateSchema();

        $logger = $this->container->get(LoggerInterface::class);

        // Server tables have no table class
        $glob = new \GlobIterator(Module::getPath('data/Tables/Server') . '/*.json');
        foreach ($glob as $fileinfo) {
            $schema = AbstractTable::loadTableDefinition($fileinfo->getPathname());
            self::setSchema(
                $logger,
                $schema,
                $database,
                \Database\AbstractTable::getObsoleteColumns($logger, $schema, $database),
                $prune
            );
            $handledTables[] = $schema['name'];
        }

        // SNMP tables have no table class
        $glob = new \GlobIterator(Module::getPath('data/Tables/Snmp') . '/*.json');
        foreach ($glob as $fileinfo) {
            $schema = AbstractTable::loadTableDefinition($fileinfo->getPathname());
            $obsoleteColumns = \Database\AbstractTable::getObsoleteColumns(
                $logger,
                $schema,
                $database
            );

            if ($schema['name'] == 'snmp_accountinfo') {
                // Preserve columns which were added through the user interface.
                $preserveColumns = array();
                // accountinfo_config may not exist yet when populating an empty
                // database. In that case, there are no obsolete columns.
                if (in_array('accountinfo_config', $database->getTableNames())) {
                    $customFieldConfig = $this->container->get(CustomFieldConfig::class);
                    $select = $customFieldConfig->getSql()->select();
                    $select->columns(array('id'))
                        ->where(
                            array(
                                'name_accountinfo' => null, // exclude system columns (TAG)
                                'account_type' => 'SNMP'
                            )
                        );
                    foreach ($customFieldConfig->selectWith($select) as $field) {
                        $preserveColumns[] = "fields_$field[id]";
                    }
                    $obsoleteColumns = array_diff($obsoleteColumns, $preserveColumns);
                }
            }
            self::setSchema(
                $logger,
                $schema,
                $database,
                $obsoleteColumns,
                $prune
            );
            $handledTables[] = $schema['name'];
        }

        // Detect obsolete tables that are present in the database but not in
        // any of the schema files.
        $obsoleteTables = array_diff($database->getTableNames(), $handledTables);
        foreach ($obsoleteTables as $table) {
            if ($prune) {
                $logger->notice("Dropping table $table...");
                $database->dropTable($table);
                $logger->notice("Done.");
            } else {
                $logger->warn("Obsolete table $table detected.");
            }
        }
    }

    /**
     * Create or update table according to schema
     *
     * @param LoggerInterface $logger Logger instance
     * @param array $schema Parsed table schema
     * @param \Nada\Database\AbstractDatabase $database Database object
     * @param string[] $obsoleteColumns List of obsolete columns to prune or warn about
     * @param bool $prune Drop obsolete tables/columns
     */
    public static function setSchema(
        $logger,
        $schema,
        $database,
        array $obsoleteColumns = array(),
        $prune = false
    ) {
        $tableName = $schema['name'];
        if (in_array($tableName, $database->getTableNames())) {
            // Table exists
            $table = $database->getTable($tableName);

            // Drop obsolete indexes which might prevent subsequent transformations.
            static::dropIndexes($logger, $table, $schema);

            // Update table engine
            if ($table instanceof Mysql and $table->getEngine() != $schema['mysql']['engine']) {
                $logger->info(
                    "Setting engine for table $tableName to {$schema['mysql']['engine']}..."
                );
                $table->setEngine($schema['mysql']['engine']);
                $logger->info('done.');
            }

            // Update table and column comments
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
                    if ($columnObj->isDifferent($column, ['type', 'length'])) {
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
                    // Since SQL types cannot be completely mapped to PHP
                    // types, a loose comparision is required, but changes
                    // to/from NULL must be taken into account.
                    if (
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
                /** @psalm-suppress InvalidArgument $primaryKey is really array<string> */
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
            if ($table instanceof Mysql) {
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

        // Detect obsolete columns that are present in the database but not in
        // the current schema.
        foreach ($obsoleteColumns as $column) {
            if ($prune) {
                $logger->notice("Dropping column $tableName.$column...");
                $table->dropColumn($column);
                $logger->notice('done.');
            } else {
                $logger->warning("Obsolete column $tableName.$column detected.");
            }
        }
    }

    /**
     * Drop indexes which are not defined in the schema.
     */
    protected static function dropIndexes(
        LoggerInterface $logger,
        NadaTable $table,
        array $schema
    ) {
        if (!isset($schema['indexes'])) {
            return;
        }

        $indexes = $schema['indexes'];
        foreach ($indexes as &$index) {
            // Remove index names for comparison
            unset($index['name']);
        }
        unset($index);

        foreach ($table->getIndexes() as $index) {
            $index = $index->toArray();
            // Remove index names for comparison, but preserve it for later reference
            $name = $index['name'];
            unset($index['name']);
            if (!in_array($index, $indexes)) {
                $logger->info("Dropping index $name...");
                $table->dropIndex($name);
                $logger->info('done.');
            }
        }
    }
}
