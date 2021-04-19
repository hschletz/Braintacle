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

use Database\AbstractTable;
use Database\Module;
use Database\Table\Clients;
use Database\Table\CustomFieldConfig;
use Database\Table\PackageDownloadInfo;
use Database\Table\Software;
use Database\Table\WindowsInstallations;
use GlobIterator;
use Laminas\Config\Factory as ConfigFactory;
use Laminas\Log\LoggerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Nada\Column\AbstractColumn;
use Nada\Database\AbstractDatabase;
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
     * @var AbstractDatabase
     */
    protected $database;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TableSchema
     */
    protected $tableSchema;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->database = $serviceLocator->get('Database\Nada');
        $this->logger = $serviceLocator->get('Library\Logger');
        $this->tableSchema = $serviceLocator->get(TableSchema::class);
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
        $connection = $this->serviceLocator->get('Db')->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $this->updateTimestampColumns();
            $this->updateTables($prune);
            $this->serviceLocator->get('Model\Config')->schemaVersion = self::VERSION;
            $connection->commit();
        } catch (Throwable $t) {
            $connection->rollback();
            throw $t;
        }
    }

    /**
     * Convert timestamp columns.
     *
     * Converts all timestamp columns in a way that allows for maximum
     * portability. In particular, portable timestamps don't support fractional
     * seconds or time zones. Some implementations like MySQL's TIMESTAMP
     * datatype (as opposed to the DATETIME type) expose nonstandard behavior
     * which is removed as well.
     */
    public function updateTimestampColumns(): void
    {
        $convertedTimestamps = $this->database->convertTimestampColumns();
        if ($convertedTimestamps) {
            $this->logger->info(
                sprintf(
                    '%d columns converted to %s.',
                    $convertedTimestamps,
                    $this->database->getNativeDatatype(AbstractColumn::TYPE_TIMESTAMP)
                )
            );
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
        $handledTables = array_merge($handledTables, $this->updateServerTables($prune));
        $handledTables = array_merge($handledTables, $this->updateSnmpTables($prune));

        // Detect obsolete tables that are present in the database but not in
        // any of the schema files.
        $obsoleteTables = array_diff($this->database->getTableNames(), $handledTables);
        foreach ($obsoleteTables as $table) {
            if ($prune) {
                $this->logger->notice("Dropping table $table...");
                $this->database->dropTable($table);
                $this->logger->notice("Done.");
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
     * Update server tables.
     *
     * @return string[] handled tables
     */
    public function updateServerTables(bool $prune): array
    {
        $handledTables = [];
        $glob = new GlobIterator(Module::getPath('data/Tables/Server') . '/*.json');
        foreach ($glob as $fileinfo) {
            $schema = ConfigFactory::fromFile($fileinfo->getPathname());
            $this->tableSchema->setSchema(
                $schema,
                AbstractTable::getObsoleteColumns($schema, $this->database),
                $prune
            );
            $handledTables[] = $schema['name'];
        }

        return $handledTables;
    }

    /**
     * Update SNMP tables.
     *
     * @return string[] handled tables
     */
    public function updateSnmpTables(bool $prune): array
    {
        $handledTables = [];
        $glob = new GlobIterator(Module::getPath('data/Tables/Snmp') . '/*.json');
        foreach ($glob as $fileinfo) {
            $schema = ConfigFactory::fromFile($fileinfo->getPathname());
            $obsoleteColumns = AbstractTable::getObsoleteColumns($schema, $this->database);

            if ($schema['name'] == 'snmp_accountinfo') {
                // Preserve columns which were added through the user interface.
                $preserveColumns = [];
                // accountinfo_config may not exist yet when populating an empty
                // database. In that case, there are no obsolete columns.
                if (in_array('accountinfo_config', $this->database->getTableNames())) {
                    $customFieldConfig = $this->serviceLocator->get(CustomFieldConfig::class);
                    $select = $customFieldConfig->getSql()->select();
                    $select->columns(['id'])->where([
                        'name_accountinfo' => null, // exclude system columns (TAG)
                        'account_type' => 'SNMP',
                    ]);
                    foreach ($customFieldConfig->selectWith($select) as $field) {
                        $preserveColumns[] = "fields_$field[id]";
                    }
                    $obsoleteColumns = array_diff($obsoleteColumns, $preserveColumns);
                }
            }
            $this->tableSchema->setSchema($schema, $obsoleteColumns, $prune);
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
