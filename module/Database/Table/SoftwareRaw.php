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

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * Raw "software" table.
 *
 * Only used for schema management. Code uses "software_installations" view and
 * the "Software" gateway.
 */
class SoftwareRaw extends \Database\AbstractTable
{
    const TABLE = 'software';

    /**
     * @codeCoverageIgnore
     */
    public function preSetSchema(array $schema, bool $prune): void
    {
        // Create/update softwareDefinitions table first because this table depends on it.
        $softwareDefinitions = $this->_serviceLocator->get('Database\Table\SoftwareDefinitions');
        $softwareDefinitions->updateSchema($prune);

        // Extra transitions on already existing table. Not necessary on table creation.
        $schemaManager = $this->connection->getSchemaManager();
        if ($schemaManager->tablesExist(['softwares'])) {
            $schemaManager->renameTable('softwares', static::TABLE);

            $columns = $schemaManager->listTableColumns(static::TABLE);
            if (!isset($columns['definition_id'])) {
                // Create column definition_id manually without the NOT NULL
                // constraint. Otherwise creation would fail if rows exist. The
                // constraint will later be added automatically, after the
                // column has been populated.
                // TODO populate values from schema
                $column = new Column('definition_id', Type::getType(Types::INTEGER), ['Notnull' => false]);
                $schemaManager->addColumn(static::TABLE, $column);

                // Populate column. The old "name" column may contain NULL which
                // is not allowed in software_definitions.name and will be
                // mapped to an empty string instead.
                $logger = $this->connection->getLogger();
                $logger->info("Transitioning {$this->table}.name values to {$this->table}.definition_id...");
                $query = $this->connection->createQueryBuilder();
                $query->select('id')
                      ->from(SoftwareDefinitions::TABLE)
                      ->where(SoftwareDefinitions::TABLE . ".name = COALESCE(software.name, '')");
                $result = $this->connection->executeStatement("UPDATE software SET definition_id = ($query)");
                $logger->info(sprintf('done, %d names transitioned.', $result));
            }
        }
    }
}
