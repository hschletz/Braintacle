<?php

/**
 * Tests for LoggingEventListener.
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

namespace Database\Test\Event;

use Database\Event\LoggingEventListener;
use Database\Schema\TableDiff as ExtendedTableDiff;
use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Laminas\Log\LoggerInterface;
use LogicException;

class LoggingEventListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSubscribedEvents()
    {
        $logger = $this->createStub(LoggerInterface::class);
        $listener = new LoggingEventListener($logger);

        $this->assertEquals(
            [
                Events::onSchemaAlterTable,
                Events::onSchemaCreateTable,
                Events::onSchemaDropTable,
            ],
            $listener->getSubscribedEvents()
        );
    }

    public function onSchemaAlterTableInvalidEventsProvider()
    {
        return [
            ['renamedColumns'],
            ['changedForeignKeys'],
            ['removedForeignKeys'],
        ];
    }

    /** @dataProvider onSchemaAlterTableInvalidEventsProvider */
    public function testOnSchemaAlterTableInvalidEvents($field)
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('FIXME: operation not implemented');

        $tableDiff = new TableDiff('foo');
        $tableDiff->$field = ['bar'];

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createStub(LoggerInterface::class);
        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableTableRenamed()
    {
        $tableDiff = new TableDiff('old_name');
        $tableDiff->newName = 'new_name';

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Renaming table old_name to new_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableColumnAdded()
    {
        $tableDiff = new TableDiff('table_name');
        $tableDiff->addedColumns[] = new Column('column_name', Type::getType(Types::INTEGER));

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Creating column table_name.column_name (integer)');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function onSchemaAlterTableColumnChangedProvider()
    {
        return [
            ['default', 'new_value', 'new_value'],
            ['type', Type::getType(Types::TEXT), 'text'],
            ['unsigned', false, 'FALSE'],
            ['unsigned', true, 'TRUE'],
        ];
    }

    /** @dataProvider onSchemaAlterTableColumnChangedProvider */
    public function testOnSchemaAlterTableColumnChanged($property, $newValue, $valueFormatted)
    {
        $tableDiff = new TableDiff('table_name');
        $tableDiff->changedColumns[] = new ColumnDiff(
            'to_column',
            new Column('to_column', Type::getType(Types::STRING), [$property => $newValue]),
            [$property],
            new Column('from_column', Type::getType(Types::STRING)),
        );

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('notice')
               ->with("Altering column table_name.from_column ($property: $valueFormatted)");

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableColumnRemoved()
    {
        $tableDiff = new TableDiff('table_name');
        $tableDiff->removedColumns[] = new Column('column_name', Type::getType(Types::INTEGER));

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Dropping column table_name.column_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableIndexAdded()
    {
        $tableDiff = new TableDiff('table_name');
        $tableDiff->addedIndexes = [
            new Index('index_name', ['foo'], false, false),
            new Index('primary_key', ['foo'], false, true),
        ];

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
               ->method('notice')
               ->withConsecutive(
                   ['Creating index index_name on table table_name'],
                   ['Creating primary key primary_key on table table_name'],
               );

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableIndexChanged()
    {
        $tableDiff = new TableDiff('table_name');
        $tableDiff->changedIndexes[] = new Index('index_name', ['foo']);

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Altering index index_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableIndexRemoved()
    {
        $tableDiff = new TableDiff('table_name');
        $tableDiff->removedIndexes[] = new Index('index_name', ['foo']);

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Dropping index index_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableIndexRenamed()
    {
        $tableDiff = new TableDiff('table_name');
        $tableDiff->renamedIndexes['old_name'] = new Index('new_name', ['foo']);

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Renaming index old_name to new_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableForeignKeyAdded()
    {
        $localTable = new Table('local_table');

        $constraint = new ForeignKeyConstraint(
            ['local_column'],
            'foreign_table',
            ['foreign_column'],
            'constraint_name'
        );
        $constraint->setLocalTable($localTable);

        $tableDiff = new TableDiff('local_table');
        $tableDiff->addedForeignKeys[] = $constraint;

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('info')
               ->with('Creating foreign key constraint constraint_name on table local_table');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableUniqueConstraintAdded()
    {
        $table = new Table('table_name');

        $tableDiff = new ExtendedTableDiff(null, $table);
        $tableDiff->addedUniqueConstraints[] = new UniqueConstraint('constraint_name', ['foo']);

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('info')
               ->with('Creating unique constraint constraint_name on table table_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableUniqueConstraintRemoved()
    {
        $table = new Table('table_name');

        $tableDiff = new ExtendedTableDiff(null, $table);
        $tableDiff->removedUniqueConstraints[] = new UniqueConstraint('constraint_name', ['foo']);

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('notice')
               ->with('Dropping unique constraint constraint_name from table table_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaAlterTableOptionChanged()
    {
        $table = new Table('table_name');

        $tableDiff = new ExtendedTableDiff(null, $table);
        $tableDiff->changedOptions['option_name'] = 'value';

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaAlterTableEventArgs($tableDiff, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('info')
               ->with('Changing table_name option_name to value');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaAlterTable($eventArgs);
    }

    public function testOnSchemaCreateTable()
    {
        $table = new Table('table_name');

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaCreateTableEventArgs($table, [], [], $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Creating table table_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaCreateTable($eventArgs);
    }

    public function testOnSchemaDropTable()
    {
        $table = new Table('table_name');

        $platform = $this->createStub(AbstractPlatform::class);
        $eventArgs = new SchemaDropTableEventArgs($table, $platform);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with('Dropping table table_name');

        $listener = new LoggingEventListener($logger);

        $listener->onSchemaDropTable($eventArgs);
    }
}
