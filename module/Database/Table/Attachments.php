<?php

/**
 * "temp_files" table
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

use Laminas\Db\Sql\Predicate\Operator;

/**
 * "temp_files" table
 */
class Attachments extends \Database\AbstractTable
{
    const TABLE = 'temp_files';

    /**
     * Client attachment
     */
    const OBJECT_TYPE_CLIENT = 'accountinfo';

    /**
     * SNMP-scanned device attachment
     */
    const OBJECT_TYPE_SNMP = 'snmp_accountinfo';

    /**
     * @codeCoverageIgnore
     */
    protected function preSetSchema(array $schema, bool $prune): void
    {
        if ($this->connection->getSchemaManager()->tablesExist([static::TABLE])) {
            $count = $this->delete([
                new Operator('table_name', '!=', self::OBJECT_TYPE_CLIENT),
                new Operator('table_name', '!=', self::OBJECT_TYPE_SNMP),
            ]);
            if ($count) {
                $this->connection->getLogger()->info("Deleted $count unsupported attachments.");
            }
        }
    }
}
