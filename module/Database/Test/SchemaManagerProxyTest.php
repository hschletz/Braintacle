<?php

/**
 * Tests for SchemaManager proxy.
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

namespace Database\Test;

use Database\Connection;
use Database\Event\Events as ExtendedEvents;
use Database\SchemaManagerProxy;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Schema\View;

class SchemaManagerProxyTest extends \PHPUnit\Framework\TestCase
{
    public function testCall()
    {
        $result = ['column'];

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listTableColumns')->with('tableName', 'databaseName')->willReturn($result);

        $proxy = $this->createPartialMock(SchemaManagerProxy::class, ['getWrappedSchemaManager']);
        $proxy->method('getWrappedSchemaManager')->willReturn($schemaManager);

        $this->assertSame($result, $proxy->listTableColumns('tableName', 'databaseName'));
    }

    public function dropConstraintProvider()
    {
        return [
            [new Index('constraint_name', ['foo'], false, true), 'removedIndexes'],
            [new UniqueConstraint('constraint_name', ['foo']), 'removedUniqueConstraints'],
            [new ForeignKeyConstraint(['foo'], 'foreign_table', ['foo'], 'constraint_name'), 'removedForeignKeys'],
        ];
    }

    /** @dataProvider dropConstraintProvider */
    public function testDropConstraint($constraint, $property)
    {
        $table = $this->createStub(Table::class);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())->method('dropConstraint')->with($constraint, $table);

        $platform = $this->createStub(AbstractPlatform::class);

        $validator = function (SchemaAlterTableEventArgs $eventArgs) use ($constraint, $property, $table, $platform) {
            $tableDiff = $eventArgs->getTableDiff();

            return
                $tableDiff->fromTable === $table and
                $tableDiff->$property == ['constraint_name' => $constraint] and
                $eventArgs->getPlatform() === $platform;
        };

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->once())
                     ->method('dispatchEvent')
                     ->with(Events::onSchemaAlterTable, $this->callback($validator));

        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getEventManager')->willReturn($eventManager);

        $proxy = new SchemaManagerProxy($schemaManager, $connection);
        $proxy->dropConstraint($constraint, $table);
    }

    public function testDropConstraintWithTableName()
    {
        $tableValidator = function (Table $table) {
            return $table->getName() == 'table_name';
        };

        $constraint = new UniqueConstraint('constraint_name', ['foo']);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
                      ->method('dropConstraint')
                      ->with($constraint, $this->callback($tableValidator));

        $platform = $this->createStub(AbstractPlatform::class);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->once())
                     ->method('dispatchEvent')
                     ->with(
                         Events::onSchemaAlterTable,
                         $this->callback(function (SchemaAlterTableEventArgs $eventArgs) use ($tableValidator) {
                            return $tableValidator($eventArgs->getTableDiff()->fromTable);
                         })
                     );

        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getEventManager')->willReturn($eventManager);

        $proxy = new SchemaManagerProxy($schemaManager, $connection);
        $proxy->dropConstraint($constraint, 'table_name');
    }

    public function testHasViewWithoutSchema()
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('supportsSchemas')->willReturn(false);
        $platform->expects($this->never())->method('getDefaultSchemaName');

        $proxy = $this->createPartialMock(SchemaManagerProxy::class, ['__call']);
        $proxy->method('__call')->willReturnMap([
            ['getDatabasePlatform', [], $platform],
            ['listViews', [], ['view' => $this->createStub(View::class)]],
        ]);

        $this->assertTrue($proxy->hasView('view'));
        $this->assertFalse($proxy->hasView('view2'));
    }

    public function testHasViewWithSchema()
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('supportsSchemas')->willReturn(true);
        $platform->method('getDefaultSchemaName')->willReturn('schema');

        $proxy = $this->createPartialMock(SchemaManagerProxy::class, ['__call']);
        $proxy->method('__call')->willReturnMap([
            ['getDatabasePlatform', [], $platform],
            ['listViews', [], ['schema.view' => $this->createStub(View::class)]],
        ]);

        $this->assertTrue($proxy->hasView('view'));
        $this->assertFalse($proxy->hasView('view2'));
    }

    public function testCreateView()
    {
        $view = $this->createStub(View::class);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())->method('createView')->with($view);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->once())
                     ->method('dispatchEvent')
                     ->with(
                         ExtendedEvents::onSchemaCreateView,
                         $this->callback(function ($eventArgs) use ($view) {
                             return $eventArgs->getView() === $view;
                         })
                     );

        $connection = $this->createStub(Connection::class);
        $connection->method('getEventManager')->willReturn($eventManager);

        $proxy = new SchemaManagerProxy($schemaManager, $connection);
        $proxy->createView($view);
    }
}
