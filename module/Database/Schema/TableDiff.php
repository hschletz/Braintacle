<?php

/**
 * Extended Table Diff.
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

use Doctrine\DBAL\Schema\Table;

/**
 * Extended Table Diff.
 */
class TableDiff extends \Doctrine\DBAL\Schema\TableDiff
{
    /**
     * @var \Doctrine\DBAL\Schema\UniqueConstraint[]
     */
    public $removedUniqueConstraints = [];

    /**
     * @var \Doctrine\DBAL\Schema\UniqueConstraint[]
     */
    public $addedUniqueConstraints = [];

    /**
     * @var string[]
     */
    public $changedOptions = [];

    /**
     * @param \Doctrine\DBAL\Schema\TableDiff|null $basicDiff Basic Table diff to clone
     * @param Table $fromTable Used for intialization if $basicDiff is NULL.
     */
    public function __construct(?\Doctrine\DBAL\Schema\TableDiff $basicDiff, Table $fromTable)
    {
        if ($basicDiff) {
            foreach ($basicDiff as $property => $value) {
                $this->$property = $value;
            }
        } else {
            $this->name = $fromTable->getName();
            $this->fromTable = $fromTable;
            $this->addedColumns = []; // Base class only initializes in constructor.
        }
    }
}
