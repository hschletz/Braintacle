<?php

/**
 * "software" table
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Table;

/**
 * Raw "software" table.
 *
 * Only used for schema management. Code uses "software_installations" view and
 * the "Software" gateway.
 */
class SoftwareRaw extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'software';

        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function preSetSchema($logger, $schema, $database, $prune)
    {
        // Create/update softwareDefinitions table first because this table depends on it.
        $softwareDefinitions = $this->_serviceLocator->get('Database\Table\SoftwareDefinitions');
        $softwareDefinitions->updateSchema($prune);

        // Extra transitions on already existing table. Not necessary on table creation.
        $tables = $database->getTableNames();
        if (in_array('softwares', $tables)) {
            $this->rename($logger, $database, 'softwares');

            $table = $database->getTable($this->table);
            $columns = $table->getColumns();
            if (!isset($columns['definition_id'])) {
                // Create column definition_id manually without the NOT NULL
                // constraint. Otherwise creation would fail if rows exist. The
                // constraint will later be added automatically, after the
                // column has been populated.
                $logger->info("Creating column {$this->table}.definition_id...");
                $columnData = $schema['columns'][array_search('definition_id', array_column($schema['columns'], 'name'))];
                $columnData['notnull'] = false;
                $table->addColumnObject($database->createColumnFromArray($columnData));
                $logger->info('done.');

                // Populate column. The old "name" column may contain NULL which
                // is not allowed in software_definitions.name and will be
                // mapped to an empty string instead.
                $logger->info("Transitioning {$this->table}.name values to {$this->table}.definition_id...");
                $query = <<<EOT
                    UPDATE {$this->table} SET definition_id = (
                        SELECT id
                        FROM software_definitions
                        WHERE software_definitions.name = COALESCE({$this->table}.name, '')
                    )
EOT;
                $result = $this->adapter->query($query, \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
                $logger->info(sprintf('done, %d names transitioned.', $result->getAffectedRows()));
            }
        }
    }
}
