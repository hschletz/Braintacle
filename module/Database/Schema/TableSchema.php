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

use Laminas\Log\LoggerInterface;
use Nada\Database\AbstractDatabase;
use Nada\Table\AbstractTable as NadaTable;

/**
 * Table schema management class
 *
 * @codeCoverageIgnore
 */
class TableSchema
{
    /**
     * @var NadaTable
     */
    protected $table;

    /**
     * @var array
     */
    protected $schema;

    /**
     * @var AbstractDatabase
     */
    protected $database;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(AbstractDatabase $database, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Create or update table according to schema
     */
    public function setSchema(array $schema, array $obsoleteColumns = [], bool $prune = false)
    {
        if (in_array($schema['name'], $this->database->getTableNames())) {
            $this->update($schema);
        } else {
            $this->create($schema);
        }
        $this->handleObsoleteColumns($obsoleteColumns, $prune);
    }

    /**
     * Create new table.
     */
    public function create(array $schema): void
    {
        $this->schema = $schema;

        $this->logger->info("Creating table '$schema[name]'...");

        $this->table = $this->database->createTable($schema['name'], $schema['columns'], $schema['primary_key']);
        $this->table->setComment($schema['comment']);

        if ($this->database->isMySql()) {
            $this->updateEngine();
            $this->table->setCharset('utf8mb4');
        }

        $this->logger->info('done.');

        $this->createIndexes();
    }

    /**
     * Update existing table.
     */
    public function update(array $schema): void
    {
        $this->schema = $schema;

        $this->table = $this->database->getTable($schema['name']);

        $this->dropIndexes(); // Obsolete indexes might prevent subsequent transformations.
        $this->updateEngine();
        $this->updateComment();
        $this->updateColumns();
        $this->updatePrimaryKey();
        $this->createIndexes();
    }

    /**
     * Update engine.
     */
    public function updateEngine(): void
    {
        if (
            $this->table->getDatabase()->isMysql() and
            $this->table->getEngine() != $this->schema['mysql']['engine']
        ) {
            $engine = $this->schema['mysql']['engine'];
            $this->logger->info(sprintf('Setting engine for table %s to %s...', $this->table->getName(), $engine));
            $this->table->setEngine($engine);
            $this->logger->info('done.');
        }
    }

    /**
     * Update comment.
     */
    public function updateComment(): void
    {
        if ($this->schema['comment'] != $this->table->getComment()) {
            $this->table->setComment($this->schema['comment']);
        }
    }

    /**
     * Update columns.
     */
    public function updateColumns(): void
    {
        $columnSchema = new ColumnSchema($this->logger);
        $columns = $this->table->getColumns();
        foreach ($this->schema['columns'] as $column) {
            if (isset($columns[$column['name']])) {
                $columnSchema->update($column, $columns[$column['name']]);
            } else {
                $columnSchema->create($column, $this->table);
            }
        }
    }

    /**
     * Drop or warn about obsolete columns.
     */
    public function handleObsoleteColumns(array $obsoleteColumns, bool $prune): void
    {
        $tableName = $this->table->getName();
        foreach ($obsoleteColumns as $column) {
            if ($prune) {
                $this->logger->notice("Dropping column $tableName.$column...");
                $this->table->dropColumn($column);
                $this->logger->notice('done.');
            } else {
                $this->logger->warn("Obsolete column $tableName.$column detected.");
            }
        }
    }

    /**
     * Update table's primary key.
     */
    public function updatePrimaryKey(): void
    {
        $primaryKey = $this->table->getPrimaryKey();
        if ($primaryKey) {
            foreach ($primaryKey as &$column) {
                $column = $column->getName();
            }
            unset($column);
        } else {
            $primaryKey = [];
        }

        if ($this->schema['primary_key'] != $primaryKey) {
            $this->logger->info(
                sprintf(
                    'Changing PK of %s from (%s) to (%s)...',
                    $this->table->getName(),
                    implode(', ', $primaryKey),
                    implode(', ', $this->schema['primary_key'])
                )
            );
            $this->table->setPrimaryKey($this->schema['primary_key']);
            $this->logger->info('done.');
        }
    }

    /**
     * Create missing indexes.
     */
    public function createIndexes(): void
    {
        //Ignore name for comparision with existing indexes.
        if (isset($this->schema['indexes'])) {
            foreach ($this->schema['indexes'] as $index) {
                if (!$this->table->hasIndex($index['columns'], $index['unique'])) {
                    $this->logger->info("Creating index '$index[name]'...");
                    $this->table->createIndex($index['name'], $index['columns'], $index['unique']);
                    $this->logger->info('done.');
                }
            }
        }
    }

    /**
     * Drop indexes which are not defined in the schema.
     */
    protected function dropIndexes()
    {
        if (!isset($this->schema['indexes'])) {
            return;
        }

        $indexes = $this->schema['indexes'];
        foreach ($indexes as &$index) {
            // Remove index names for comparison
            unset($index['name']);
        }
        unset($index);

        foreach ($this->table->getIndexes() as $index) {
            $index = $index->toArray();
            // Remove index names for comparison, but preserve it for later reference
            $name = $index['name'];
            unset($index['name']);
            if (!in_array($index, $indexes)) {
                $this->logger->info("Dropping index $name...");
                $this->table->dropIndex($name);
                $this->logger->info('done.');
            }
        }
    }
}
