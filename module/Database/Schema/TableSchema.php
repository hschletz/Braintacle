<?php

/**
 * Table schema management class
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

namespace Database\Schema;

use Database\Connection;
use Database\Table\CustomFieldConfig;
use Database\Table\CustomFields;
use Doctrine\DBAL\Schema\Table;
use Laminas\Log\LoggerInterface;
use Nada\Database\AbstractDatabase;

/**
 * Table schema management class
 *
 * @codeCoverageIgnore
 */
class TableSchema
{
    /**
     * @var AbstractDatabase
     */
    protected $database;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Comparator
     */
    protected $comparator;

    /**
     * @var SchemaManagerProxy
     */
    protected $schemaManager;

    /**
     * @var CustomFieldConfig
     */
    protected $customFieldConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        AbstractDatabase $database,
        Connection $connection,
        Comparator $comparator,
        CustomFieldConfig $customFieldConfig
    ) {
        $this->database = $database;
        $this->connection = $connection;
        $this->comparator = $comparator;
        $this->schemaManager = $connection->getSchemaManager();
        $this->customFieldConfig = $customFieldConfig;
        $this->logger = $connection->getLogger();
    }

    /**
     * Create or update table according to schema
     */
    public function setSchema(array $schema, bool $prune)
    {
        $parser = new SchemaParser($this->connection->getDatabasePlatform());
        $table = $parser->parseTable($schema);
        if ($this->schemaManager->tablesExist([$schema['name']])) {
            $this->update($table, $prune);
        } else {
            $this->create($table);
        }

        // Temporary workaround for tests
        if (getenv('BRAINTACLE_TEST_DATABASE') and !in_array($schema['name'], $this->database->getTableNames())) {
            $table = $this->database->createTable($schema['name'], $schema['columns'], $schema['primary_key']);
            if (isset($schema['indexes'])) {
                foreach ($schema['indexes'] as $index) {
                    $table->createIndex($index['name'], $index['columns'], $index['unique']);
                }
            }
        }
    }

    /**
     * Create new table.
     */
    public function create(Table $table): void
    {
        $this->schemaManager->createTable($table);
    }

    /**
     * Update existing table.
     */
    public function update(Table $toTable, bool $prune): void
    {
        $tableName = $toTable->getName();
        $fromTable = $this->schemaManager->listTableDetails($tableName);

        $tableDiff = $this->comparator->diffTable($fromTable, $toTable);
        if ($tableDiff) {
            if ($tableName == CustomFields::TABLE or $tableName == 'snmp_accountinfo') {
                // Columns which were added through the user interface are not
                // present in the schema file. Preserve these columns by
                // removing them from the diff.
                foreach ($this->customFieldConfig->getTargetColumnNames() as $column) {
                    unset($tableDiff->removedColumns[$column]);
                }
            }

            if (!$prune) {
                foreach ($tableDiff->removedColumns as $column) {
                    $this->logger->warn("Obsolete column $tableName.{$column->getName()} detected.");
                }
                $tableDiff->removedColumns = [];
            }

            $this->schemaManager->alterTable($tableDiff);
        }
    }
}
