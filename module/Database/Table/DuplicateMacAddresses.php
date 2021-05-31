<?php

/**
 * "blacklist_macaddresses" table
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

namespace Database\Table;

/**
 * "blacklist_macaddresses" table
 */
class DuplicateMacAddresses extends \Database\AbstractTable
{
    const TABLE = 'blacklist_macaddresses';

    /**
     * @codeCoverageIgnore
     */
    protected function preSetSchema(array $schema, bool $prune): void
    {
        // Drop obsolete autoincrement column to avoid MySQL error when setting new PK
        $schemaManager = $this->connection->getSchemaManager();
        if ($schemaManager->tablesExist([static::TABLE])) {
            $columns = $schemaManager->listTableColumns(static::TABLE);
            if (isset($columns['id'])) {
                $schemaManager->dropColumn(static::TABLE, 'id');
            }
        }
    }
}
