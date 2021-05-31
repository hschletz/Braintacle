<?php

/**
 * Database schema management class
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
use Database\Module;
use Database\Table\Clients;
use Database\Table\PackageDownloadInfo;
use Database\Table\Software;
use Database\Table\WindowsInstallations;
use Doctrine\DBAL\Schema\Identifier;
use GlobIterator;
use Laminas\Config\Factory as ConfigFactory;
use Laminas\Log\LoggerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Throwable;

/**
 * Database schema management class
 *
 * This class contains all functionality to manage the database schema and to
 * initialize and migrate data.
 *
 * @codeCoverageIgnore
 */
class DatabaseSchema
{
    /**
     * Latest version of data transformations that cannot be detected automatically
     */
    const VERSION = 8;

    /**
     * Service locator
     * @var \Laminas\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TableSchema
     */
    protected $tableSchema;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->tableSchema = $serviceLocator->get(TableSchema::class);
        $this->connection = $serviceLocator->get(Connection::class);
        $this->logger = $this->connection->getLogger();
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
        // Transactions don't work properly with mysql platform.
        $platform = $this->connection->getDatabasePlatform()->getName();
        if ($platform != 'mysql') {
            $this->connection->beginTransaction();
        }
        try {
            $this->fixTimestampColumns();
            $this->updateTables($prune);
            $this->serviceLocator->get('Model\Config')->schemaVersion = self::VERSION;
            if ($platform != 'mysql') {
                $this->connection->commit();
            }
        } catch (Throwable $t) {
            try {
                $this->connection->rollBack();
            } catch (Throwable $t2) {
            }
            throw $t;
        }
    }

    /**
     * Apply fixes to timestamp columns which are not handled by standard schema operations.
     *
     * For PostgreSQL, set precision of timestamp columns to 0 (fractional
     * seconds are not portable).
     */
    public function fixTimestampColumns(): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            $platform = $this->connection->getDatabasePlatform();

            $query = $this->connection->createQueryBuilder();
            $query->select('table_name', 'column_name')
                ->from('information_schema.columns')
                ->where('datetime_precision != 0')
                ->andWhere("table_schema = 'public'")
                ->andWhere("data_type IN('timestamp with time zone', 'timestamp without time zone')");

            foreach ($query->execute()->iterateAssociative() as $column) {
                $this->logger->notice("Setting precision of column $column[table_name].$column[column_name] to 0");
                $tableName = (new Identifier($column['table_name']))->getQuotedName($platform);
                $columnName = (new Identifier($column['column_name']))->getQuotedName($platform);
                $this->connection->executeStatement(
                    "ALTER TABLE $tableName ALTER COLUMN $columnName TYPE timestamp(0) without time zone"
                );
            }
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
        $handledTables = $this->updateTablesViaClass($prune);
        $this->updateViews();
        $handledTables = array_merge($handledTables, $this->updateTablesFromDirectory('Server', $prune));
        $handledTables = array_merge($handledTables, $this->updateTablesFromDirectory('Snmp', $prune));

        // Detect obsolete tables that are present in the database but not in
        // any of the schema files.
        $schemaManager = $this->connection->getSchemaManager();
        $obsoleteTables = array_diff($schemaManager->listTableNames(), $handledTables);
        foreach ($obsoleteTables as $table) {
            if ($prune) {
                $schemaManager->dropTable($table);
            } else {
                $this->logger->warn("Obsolete table $table detected.");
            }
        }
    }

    /**
     * Update tables with schema file and corresponding class.
     *
     * @return string[] handled tables
     */
    public function updateTablesViaClass(bool $prune): array
    {
        $handledTables = [];
        $glob = new GlobIterator(Module::getPath('data/Tables') . '/*.json');
        foreach ($glob as $fileinfo) {
            $tableClass = $fileinfo->getBaseName('.json');
            $table = $this->serviceLocator->get('Database\Table\\' . $tableClass);
            $table->updateSchema($prune);
            $handledTables[] = $table->table;
        }

        return $handledTables;
    }

    /**
     * Update tables from schema files in a directory.
     *
     * @return string[] handled tables
     */
    public function updateTablesFromDirectory(string $directory, bool $prune): array
    {
        $handledTables = [];
        $glob = new GlobIterator(Module::getPath('data/Tables/' . $directory) . '/*.json');
        foreach ($glob as $fileinfo) {
            $schema = ConfigFactory::fromFile($fileinfo->getPathname());
            $this->tableSchema->setSchema($schema, $prune);
            $handledTables[] = $schema['name'];
        }

        return $handledTables;
    }

    /**
     * Update views.
     */
    public function updateViews(): void
    {
        $this->serviceLocator->get(Clients::class)->updateSchema();
        $this->serviceLocator->get(PackageDownloadInfo::class)->updateSchema();
        $this->serviceLocator->get(WindowsInstallations::class)->updateSchema();
        $this->serviceLocator->get(Software::class)->updateSchema();
    }
}
