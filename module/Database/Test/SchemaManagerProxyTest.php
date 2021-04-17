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

use Database\SchemaManagerProxy;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\View;

class SchemaManagerProxyTest extends \PHPUnit\Framework\TestCase
{
    public function testCall()
    {
        $result = ['column'];

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listTableColumns')->with('tableName', 'databaseName')->willReturn($result);

        $proxy = new SchemaManagerProxy($schemaManager);
        $this->assertSame($result, $proxy->listTableColumns('tableName', 'databaseName'));
    }

    public function testHasViewWithoutSchema()
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('supportsSchemas')->willReturn(false);
        $platform->expects($this->never())->method('getDefaultSchemaName');

        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $schemaManager->method('getDatabasePlatform')->willReturn($platform);
        $schemaManager->method('listViews')->willReturn(['view' => $this->createStub(View::class)]);

        $proxy = new SchemaManagerProxy($schemaManager);
        $this->assertTrue($proxy->hasView('view'));
        $this->assertFalse($proxy->hasView('view2'));
    }

    public function testHasViewWithSchema()
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('supportsSchemas')->willReturn(true);
        $platform->method('getDefaultSchemaName')->willReturn('schema');

        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $schemaManager->method('getDatabasePlatform')->willReturn($platform);
        $schemaManager->method('listViews')->willReturn(['schema.view' => $this->createStub(View::class)]);

        $proxy = new SchemaManagerProxy($schemaManager);
        $this->assertTrue($proxy->hasView('view'));
        $this->assertFalse($proxy->hasView('view2'));
    }
}
