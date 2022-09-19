<?php

/**
 * "accountinfo" table
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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
 * "accountinfo" table
 */
class CustomFields extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'accountinfo';
        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getObsoleteColumns($logger, $schema, $database)
    {
        $obsoleteColumns = parent::getObsoleteColumns($logger, $schema, $database);
        // Preserve columns which were added through the user interface.
        $preserveColumns = array();
        // accountinfo_config may not exist yet when populating an empty
        // database. In that case, there are no obsolete columns.
        if (in_array('accountinfo_config', $database->getTableNames())) {
            $fields = $database->query(
                "SELECT id FROM accountinfo_config WHERE name_accountinfo IS NULL AND account_type = 'COMPUTERS'"
            );
            foreach ($fields as $field) {
                $preserveColumns[] = "fields_$field[id]";
            }
        }
        return array_diff($obsoleteColumns, $preserveColumns);
    }
}
