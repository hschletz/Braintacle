<?php

/**
 * "itmgmt_comments" table
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Psr\Container\ContainerInterface;

/**
 * "itmgmt_comments" table
 */
class Comments extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'itmgmt_comments';
        parent::__construct($container);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function preSetSchema($schema, $database, $prune)
    {
        // Migration: if the "visible" column still exists, permanently delete
        // rows that are marked as deleted before the column gets dropped.
        if (in_array($this->table, $database->getTableNames())) {
            $columns = $database->getTable($this->table)->getColumns();
            if (isset($columns['visible'])) {
                $this->logger->info('Pruning deleted comments');
                $this->delete(array('visible' => 0));
                $this->logger->info('done.');
            }
        }
    }
}
