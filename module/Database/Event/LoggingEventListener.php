<?php

/**
 * Logging listener for schema events
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

namespace Database\Event;

use Database\Schema\TableDiff as ExtendedTableDiff;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Laminas\Log\LoggerInterface;
use LogicException;

/**
 * Logging listener for schema events
 */
class LoggingEventListener implements EventSubscriber
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onSchemaAlterTable,
            Events::onSchemaCreateTable,
            Events::onSchemaDropTable,
        ];
    }

    protected function log(string $level, string $template, ...$args): void
    {
        $this->logger->$level(vsprintf($template, $args));
    }

    public function onSchemaAlterTable(SchemaAlterTableEventArgs $args)
    {
        $tableDiff = $args->getTableDiff();
        if (
            $tableDiff->renamedColumns or
            $tableDiff->changedForeignKeys or
            $tableDiff->removedForeignKeys
        ) {
            throw new LogicException('FIXME: operation not implemented');
        }

        if ($tableDiff->newName) {
            $this->log('info', 'Renaming table %s to %s', $tableDiff->name, $tableDiff->newName);
        }
        foreach ($tableDiff->addedColumns as $column) {
            $this->log(
                'info',
                'Creating column %s.%s (%s)',
                $tableDiff->name,
                $column->getName(),
                $column->getType()->getName()
            );
        }
        foreach ($tableDiff->changedColumns as $columnDiff) {
            foreach ($columnDiff->changedProperties as $property) {
                $newValue = $columnDiff->column->toArray()[$property];
                if (is_bool($newValue)) {
                    $newValue = $newValue ? 'TRUE' : 'FALSE';
                }
                $this->log(
                    'notice',
                    'Altering column %s.%s (%s: %s)',
                    $tableDiff->name,
                    $columnDiff->fromColumn->getName(),
                    $property,
                    ($newValue instanceof Type) ? $newValue->getName() : $newValue
                );
            }
        }
        foreach ($tableDiff->removedColumns as $column) {
            $this->log('notice', 'Dropping column %s.%s', $tableDiff->name, $column->getName());
        }
        foreach ($tableDiff->addedIndexes as $index) {
            $this->log(
                'notice',
                'Creating %s %s on table %s',
                $index->isPrimary() ? 'primary key' : 'index',
                $index->getName(),
                $tableDiff->name
            );
        }
        foreach ($tableDiff->changedIndexes as $index) {
            $this->log('info', 'Altering index %s', $index->getName());
        }
        foreach ($tableDiff->removedIndexes as $index) {
            $this->log('notice', 'Dropping index %s', $index->getName());
        }
        foreach ($tableDiff->renamedIndexes as $oldName => $index) {
            $this->log('info', 'Renaming index %s to %s', $oldName, $index->getName());
        }
        foreach ($tableDiff->addedForeignKeys as $foreignKey) {
            $this->log(
                'info',
                'Creating foreign key constraint %s on table %s',
                $foreignKey->getName(),
                $foreignKey->getLocalTableName()
            );
        }
        if ($tableDiff instanceof ExtendedTableDiff) {
            foreach ($tableDiff->addedUniqueConstraints as $uniqueConstraint) {
                $this->log(
                    'info',
                    'Creating unique constraint %s on table %s',
                    $uniqueConstraint->getName(),
                    $tableDiff->name
                );
            }
            foreach ($tableDiff->removedUniqueConstraints as $uniqueConstraint) {
                $this->log(
                    'notice',
                    'Dropping unique constraint %s from table %s',
                    $uniqueConstraint->getName(),
                    $tableDiff->name
                );
            }
            foreach ($tableDiff->changedOptions as $option => $newValue) {
                $this->log('info', 'Changing %s %s to %s', $tableDiff->name, $option, $newValue);
            }
        }
    }

    public function onSchemaCreateTable(SchemaCreateTableEventArgs $args)
    {
        $this->log('info', 'Creating table %s', $args->getTable()->getName());
    }

    public function onSchemaDropTable(SchemaDropTableEventArgs $args)
    {
        $table = $args->getTable();
        if ($table instanceof Table) {
            $table = $table->getName();
        }
        $this->log('notice', 'Dropping table %s', $table);
    }
}
