<?php

/**
 * Compares schemas.
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

use Database\SchemaManagerProxy;
use Doctrine\DBAL\Schema\Table;

/**
 * Compares schemas.
 */
class Comparator extends \Doctrine\DBAL\Schema\Comparator
{
    /**
     * @var SchemaManagerProxy
     */
    protected $schemaManager;

    public function __construct(SchemaManagerProxy $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    public function diffTable(Table $fromTable, Table $toTable)
    {
        $this->schemaManager->prepareTable($fromTable);
        $this->schemaManager->prepareTable($toTable);

        $removedUniqueConstraints = [];
        foreach ($fromTable->getUniqueConstraints() as $uniqueConstraint) {
            $name = $uniqueConstraint->getName();
            if (!isset($toTable->getUniqueConstraints()[$name])) {
                $removedUniqueConstraints[$name] = $uniqueConstraint;
            }
        }

        $addedUniqueConstraints = [];
        foreach ($toTable->getUniqueConstraints() as $uniqueConstraint) {
            $name = $uniqueConstraint->getName();
            if (!isset($fromTable->getUniqueConstraints()[$name])) {
                $addedUniqueConstraints[$name] = $uniqueConstraint;
            }
        }

        $changedOptions = [];
        $fromTableOptions = $fromTable->getOptions();
        $toTableOptions = $toTable->getOptions();
        if (
            isset($fromTableOptions['engine']) and
            isset($toTableOptions['engine']) and
            $fromTableOptions['engine'] != $toTableOptions['engine']
        ) {
            $changedOptions['engine'] = $toTableOptions['engine'];
        }
        if (($fromTableOptions['comment'] ?? null) != ($toTableOptions['comment'] ?? null)) {
            $changedOptions['comment'] = $toTableOptions['comment'];
        }

        // Construct extended TableDiff if necessary.
        $tableDiff = parent::diffTable($fromTable, $toTable);
        if ($tableDiff or $addedUniqueConstraints or $removedUniqueConstraints or $changedOptions) {
            $tableDiff = new TableDiff($tableDiff ?: null, $fromTable);
            $tableDiff->addedUniqueConstraints = $addedUniqueConstraints;
            $tableDiff->removedUniqueConstraints = $removedUniqueConstraints;
            $tableDiff->changedOptions = $changedOptions;
        }

        return $tableDiff;
    }
}
