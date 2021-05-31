<?php

/**
 * "software_definitions" table
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
 * "software_definitions" table
 */
class SoftwareDefinitions extends \Database\AbstractTable
{
    const TABLE = 'software_definitions';

    /**
     * Migrate accepted software from old table structure?
     * @var bool
     */
    protected $_migrateAccepted;

    /**
     * Migrate ignored software from old table structure?
     * @var bool
     */
    protected $_migrateIgnored;

    /**
     * @codeCoverageIgnore
     */
    protected function preSetSchema(array $schema, bool $prune): void
    {
        $tables = $this->connection->getSchemaManager()->listTableNames();
        $tableExists = in_array(static::TABLE, $tables);
        $this->_migrateAccepted = (!$tableExists and in_array('dico_soft', $tables));
        $this->_migrateIgnored = (!$tableExists and in_array('dico_ignored', $tables));
    }

    /**
     * @codeCoverageIgnore
     */
    protected function postSetSchema(array $schema, bool $prune): void
    {
        $logger = $this->connection->getLogger();
        if ($this->_migrateAccepted) {
            $logger->info('Migrating accepted software definitions');
            $this->adapter->query(
                'INSERT INTO software_definitions (name, display) SELECT extracted, TRUE FROM dico_soft',
                \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
            );
            $logger->info('done.');
        }
        if ($this->_migrateIgnored) {
            $logger->info('Migrating ignored software definitions');
            $query = <<<'EOT'
                INSERT INTO software_definitions (name, display)
                SELECT extracted, FALSE FROM dico_ignored WHERE extracted NOT IN(SELECT name FROM software_definitions)
EOT;
            $this->adapter->query($query, \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
            $logger->info('done.');
        }

        if ($this->connection->getSchemaManager()->tablesExist(['softwares'])) {
            // Create rows for names which are not already defined.
            // softwares.name may contain NULL which is not allowed here and
            // will be mapped to an empty string instead.
            $logger->info('Migrating uncategorized software definitions');
            $query = $this->connection->createQueryBuilder();
            $query->select("COALESCE(name, '')")
                  ->distinct()
                  ->from('softwares')
                  ->where("COALESCE(name, '') NOT IN (SELECT name FROM software_definitions)");
            $result = $this->connection->executeStatement('INSERT INTO software_definitions (name) ' . $query);
            $logger->info(sprintf('done, %d definitions migrated.', $result));
        }
    }
}
