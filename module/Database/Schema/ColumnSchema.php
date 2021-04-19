<?php

/**
 * Column schema management class
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

use Laminas\Log\LoggerInterface;
use Nada\Column\AbstractColumn;
use Nada\Table\AbstractTable as NadaTable;

/**
 * Column schema management class
 *
 * @codeCoverageIgnore
 */
class ColumnSchema
{
    /**
     * @var AbstractColumn
     */
    protected $column;

    /**
     * @var array
     */
    protected $schema;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Create new column.
     */
    public function create(array $schema, NadaTable $table): void
    {
        $this->logger->info(sprintf('Creating column %s.%s...', $table->getName(), $schema['name']));
        $this->schema = $schema;
        $this->column = $table->getDatabase()->createColumnFromArray($schema);
        $table->addColumnObject($this->column);
        $this->logger->info('done.');
    }

    /**
     * Update existing column.
     */
    public function update(array $schema, AbstractColumn $column): void
    {
        $this->schema = $schema;
        $this->column = $column;
        $this->column->setComment($schema['comment']);
        $this->setType();
        $this->setDefault();
        $this->setConstraints();
    }

    /**
     * Set datatype.
     */
    public function setType(): void
    {
        if ($this->column->isDifferent($this->schema, ['type', 'length'])) {
            $this->logger->info(
                sprintf(
                    'Setting column %s.%s type to %s(%s)...',
                    $this->column->getTable()->getName(),
                    $this->schema['name'],
                    $this->schema['type'],
                    $this->schema['length']
                )
            );
            $this->column->setDatatype($this->schema['type'], $this->schema['length']);
            $this->logger->info('done.');
        }
    }

    /**
     * Set default value.
     */
    public function setDefault(): void
    {
        // Since SQL types cannot be completely mapped to PHP types, a loose
        // comparison is required, but changes to/from NULL must be taken into
        // account.
        $existingDefault = $this->column->getDefault();
        $schemaDefault = $this->schema['default'];
        if (
            $existingDefault === null and $schemaDefault !== null or
            $existingDefault !== null and $schemaDefault === null or
            $existingDefault != $schemaDefault
        ) {
            $this->logger->info(
                sprintf(
                    'Setting default value of column %s.%s to %s...',
                    $this->column->getTable()->getName(),
                    $this->schema['name'],
                    ($schemaDefault === null) ? 'NULL' : $schemaDefault
                )
            );
            $this->column->setDefault($schemaDefault);
            $this->logger->info('done.');
        }
    }

    /**
     * Set/remove NOT NULL constraint.
     */
    public function setConstraints(): void
    {
        if ($this->column->getNotNull() != $this->schema['notnull']) {
            $this->logger->info(
                sprintf(
                    '%s NOT NULL constraint on column %s.%s...',
                    $this->schema['notnull'] ? 'Setting' : 'Removing',
                    $this->column->getTable()->getName(),
                    $this->schema['name']
                )
            );
            $this->column->setNotNull($this->schema['notnull']);
            $this->logger->info('done.');
        }
    }
}
