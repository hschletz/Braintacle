<?php

/**
 * Doctrine schema manager extension
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

namespace Database;

use Database\Schema\TableDiff as ExtendedTableDiff;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;
use Laminas\Log\LoggerInterface;
use LogicException;
use ReflectionProperty;

/**
 * Doctrine schema manager extension
 */
class SchemaManagerProxy implements EventSubscriber
{
    /**
     * @var AbstractSchemaManager
     */
    protected $schemaManager;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    private $uniqueConstraints = null;

    public function __construct(
        AbstractSchemaManager $schemaManager,
        Connection $connection,
        ?LoggerInterface $logger = null
    ) {
        $this->schemaManager = $schemaManager;
        $this->connection = $connection;
        $this->logger = $logger;

        $connection->getEventManager()->addEventSubscriber($this);
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onSchemaAlterTable,
            Events::onSchemaCreateTable,
            Events::onSchemaDropTable,
        ];
    }

    /**
     * Proxy all unimplemented methods to underlying schema manager instance.
     */
    public function __call($name, $arguments)
    {
        return $this->getWrappedSchemaManager()->$name(...$arguments);
    }

    protected function getWrappedSchemaManager(): AbstractSchemaManager
    {
        return $this->schemaManager;
    }

    /**
     * Apply workarounds to a table object.
     */
    public function prepareTable(Table $table): void
    {
        if ($this->getConnection()->getDatabasePlatform()->getName() == 'sqlite') {
            // Unique constraints in SQLite are tricky. In particular, the only
            // programmatic interface to reconstruct the constraint is a stored
            // CREATE TABLE statement which would need to be parsed. As a simple
            // workaround, unique constraints are converted to unique indexes.
            // This is sufficient for testing purposes.
            // The reflection hack is required to remove the constraints because
            // Table::removeUniqueConstraint() is broken.
            $uniqueConstraints = $table->getUniqueConstraints();
            $property = new ReflectionProperty($table, 'uniqueConstraints');
            $property->setAccessible(true);
            $property->setValue($table, []);
            foreach ($uniqueConstraints as $uniqueConstraint) {
                $table->addUniqueIndex($uniqueConstraint->getColumns(), $uniqueConstraint->getName());
            }
        }
    }

    public function createTable(Table $table): void
    {
        $this->prepareTable($table);
        $this->getWrappedSchemaManager()->createTable($table);
        if ($this->getConnection()->getDatabasePlatform()->getName() == 'postgresql') {
            // Constraints are not created automatically by postgresql platform
            // implementation.
            foreach ($table->getUniqueConstraints() as $uniqueConstraint) {
                $this->createConstraint($uniqueConstraint, $table);
            }
        }
    }

    public function alterTable(TableDiff $tableDiff): void
    {
        $platform = $this->getConnection()->getDatabasePlatform();
        $tableIdentifier = (new Identifier($tableDiff->name))->getQuotedName($platform);

        // Change MySQL table engine first because other table properties may
        // rely on the new engine's capabilities. The typical scenario is
        // upgrading to an engine with enhanced capabilities (i.e. MyISAM ->
        // InnoDB) which may fail if some properties (i.e. index length) are
        // changed with the old engine still in effect.
        if (isset($tableDiff->changedOptions['engine'])) {
            $this->connection->executeStatement(
                "ALTER TABLE $tableIdentifier ENGINE = {$tableDiff->changedOptions['engine']}"
            );
        }

        // Primary keys are actually constraints, not indexes, and cannot simply
        // be changed. Instead, drop and re-add the constraint. Other indexes
        // are handled as usual.
        $changedIndexes = [];
        foreach ($tableDiff->changedIndexes as $name => $index) {
            if ($index->isPrimary()) {
                // $index holds the default name from the schema, which may
                // differ from the actual name in $name. Synthesize an Index
                // object with the correct name.
                $primaryKey = new Index(
                    $name,
                    $index->getColumns(),
                    $index->isUnique(),
                    $index->isPrimary(),
                    $index->getFlags(),
                    $index->getOptions()
                );
                $this->dropConstraint($primaryKey, $tableDiff->fromTable);
                $tableDiff->addedIndexes[$index->getName()] = $index;
            } else {
                $changedIndexes[$name] = $index;
            }
        }
        $tableDiff->changedIndexes = $changedIndexes;

        $this->schemaManager->alterTable($tableDiff);

        if ($tableDiff instanceof ExtendedTableDiff) {
            foreach ($tableDiff->removedUniqueConstraints as $uniqueConstraint) {
                $this->dropConstraint($uniqueConstraint, $tableDiff->fromTable);
            }
            foreach ($tableDiff->addedUniqueConstraints as $uniqueConstraint) {
                $this->createConstraint($uniqueConstraint, $tableDiff->fromTable);
            }
            if (isset($tableDiff->changedOptions['comment'])) {
                // AbstractPlatform::getCommentOnTableSQL() is protected, reimplement here
                $this->connection->executeStatement(
                    "COMMENT ON TABLE $tableIdentifier IS " . $platform->quoteStringLiteral(
                        (string) $tableDiff->changedOptions['comment']
                    )
                );
            }
        }
    }

    public function listTableDetails($name): Table
    {
        $table = $this->schemaManager->listTableDetails($name);

        // Default implementation lists unique constraints as unique indexes,
        // making them undistinguishable from each other. Detect unique
        // constraints and report them as such.
        $uniqueConstraints = $this->listUniqueConstraints();
        if (isset($uniqueConstraints[$name])) {
            foreach ($table->getIndexes() as $index) {
                $indexName = $index->getName();
                if (
                    $index->isUnique() and
                    !$index->isPrimary() and
                    in_array($indexName, $uniqueConstraints[$name])
                ) {
                    $columns = $index->getColumns();
                    $table->dropIndex($indexName);
                    $table->addUniqueConstraint($columns, $indexName);
                }
            }
        }

        return $table;
    }

    public function createConstraint(Constraint $constraint, $table)
    {
        if ($constraint instanceof UniqueConstraint) {
            $this->uniqueConstraints = null; // invalidate cache
            // createConstraint() generates invalid SQL when passed a
            // UniqueConstraint object. Synthesize an Index object and pass that
            // instead. This creates a proper unique constraint, not just a
            // unique index.
            $constraint = new Index($constraint->getName(), $constraint->getColumns(), true);
        }
        $this->schemaManager->createConstraint($constraint, $table);
    }

    public function dropConstraint(Constraint $constraint, $table)
    {
        $this->log(
            'notice',
            'Dropping constraint %s from table %s',
            $constraint->getName(),
            ($table instanceof Table) ? $table->getName() : $table
        );
        $this->schemaManager->dropConstraint($constraint, $table);
        if ($constraint instanceof UniqueConstraint) {
            $this->uniqueConstraints = null; // invalidate cache
        }
    }

    /**
     * List names of all unique constraints grouped by table.
     */
    public function listUniqueConstraints(): array
    {
        if ($this->uniqueConstraints === null) {
            $this->uniqueConstraints = [];
            // The information_schema query does not work with SQLite. No other
            // solution is available other than parsing the stored CREATE TABLE
            // statement. Use unique indexes with SQLite instead.
            if ($this->connection->getDatabasePlatform()->getName() != 'sqlite') {
                $query = $this->connection->createQueryBuilder();
                $query->select('table_name', 'constraint_name')
                        ->from('information_schema.table_constraints')
                        ->where("constraint_type = 'UNIQUE'");

                foreach ($query->execute()->iterateAssociative() as $row) {
                    $this->uniqueConstraints[$row['table_name']][] = $row['constraint_name'];
                }
            }
        }

        return $this->uniqueConstraints;
    }

    /**
     * Get database connection.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Query for existing view.
     */
    public function hasView(string $name): bool
    {
        $platform = $this->getDatabasePlatform();
        if ($platform->supportsSchemas()) {
            $name = $platform->getDefaultSchemaName() . '.' . $name;
        }

        return isset($this->listViews()[$name]);
    }

    /**
     * Create a view.
     */
    public function createView(View $view): void
    {
        // Log explicitly because there is no event for creating views.
        $this->log('info', 'Creating view %s', $view->getName());
        $this->schemaManager->createView($view);
    }

    /**
     * Add Column to a table.
     *
     * @param string|Table $table
     * @param Column $column
     */
    public function addColumn($table, Column $column): void
    {
        $tableName = $this->getTableName($table);
        $tableDiff = new TableDiff($tableName);
        $tableDiff->addedColumns = [$column];
        $this->schemaManager->alterTable($tableDiff);
    }

    /**
     * Drop Column from a table.
     *
     * @param string|Table $table
     * @param string|Column $column
     */
    public function dropColumn($table, $column): void
    {
        if (!($table instanceof Table)) {
            $table = $this->listTableDetails($table);
        }
        if (!$column instanceof Column) {
            $column = $table->getColumn($column);
        }

        $tableDiff = new TableDiff($table->getName());
        $tableDiff->fromTable = $table;
        $tableDiff->removedColumns[$column->getName()] = $column;

        $this->schemaManager->alterTable($tableDiff);
    }

    /**
     * Get Table name from string or Table object.
     * @param string|Table $table
     */
    protected function getTableName($table): string
    {
        if ($table instanceof Table) {
            return $table->getName();
        } else {
            return $table;
        }
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
                    'Dropping unique constraint %s on table %s',
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

    /**
     * Send message to logger.
     */
    protected function log(string $level, string $template, ...$args): void
    {
        if ($this->logger) {
            $this->logger->$level(vsprintf($template, $args));
        }
    }
}
