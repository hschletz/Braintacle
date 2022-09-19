<?php

/**
 * "network_devices" table
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
 * "network_devices" table
 */
class NetworkDevicesIdentified extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'network_devices';
        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function preSetSchema($logger, $schema, $database, $prune)
    {
        // Drop obsolete autoincrement column to avoid MySQL error when setting new PK
        $this->dropColumnIfExists($logger, $database, 'id');

        // There used to be a column named "user". On PostgreSQL, dropping that
        // column would fail without quoting. Since the default pruning code
        // does not quote, delete the column manually with quoting temporarily
        // enabled.
        if ($prune and $database->isPgsql()) {
            $keywords = $database->quoteKeywords;
            $database->quoteKeywords[] = 'user';
            try {
                $this->dropColumnIfExists($logger, $database, 'user');
            } finally {
                // Always reset quoteKeywords.
                $database->quoteKeywords = $keywords;
            }
        }
    }
}
