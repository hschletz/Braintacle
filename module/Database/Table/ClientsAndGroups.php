<?php

/**
 * "hardware" table
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
 * "hardware" table
 *
 * This table should only be used for write operations and groups. Use the
 * "Clients" view for SELECT queries on clients.
 */
class ClientsAndGroups extends \Database\AbstractTable
{
    const TABLE = 'hardware';

    /**
     * @codeCoverageIgnore
     */
    protected function postSetSchema(array $schema, bool $prune): void
    {
        // obsolete feature which was never supported.
        $query = $this->connection->createQueryBuilder();
        if ($query->delete(static::TABLE)->where("deviceid = '_DOWNLOADGROUP_'")->execute()) {
            $this->connection->getLogger()->warn('Obsolete download groups found and deleted.');
        }
    }
}
