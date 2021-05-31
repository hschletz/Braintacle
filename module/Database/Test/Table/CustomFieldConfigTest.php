<?php

/**
 * Tests for the CustomFieldConfig class
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

namespace Database\Test\Table;

use Database\Connection;
use Database\Table\CustomFieldConfig;
use Database\Table\CustomFields;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Laminas\ServiceManager\ServiceManager;
use RuntimeException;

/**
 * Tests for the CustomFieldConfig class
 */
class CustomFieldConfigTest extends AbstractTest
{
    protected static $_nada;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$_nada = static::$serviceManager->get('Database\Nada');
    }

    public function setUp(): void
    {
        // Reset columns from CustomFields table. This requires the
        // CustomFieldConfig table to be truncated first because
        // CustomFields::updateSchema() would not drop any columns with a
        // matching record in CustomFieldConfig.
        $connection = $this->getDatabaseConnection();
        $connection->executeStatement($connection->getDatabasePlatform()->getTruncateTableSQL(CustomFieldConfig::TABLE));
        $customFields = static::$serviceManager->get(CustomFields::class);
        $customFields->updateSchema(true);

        // Create the columns matching the CustomFieldConfig fixture.
        $tableDiff = new TableDiff(CustomFields::TABLE);
        $tableDiff->addedColumns = [
            new Column('fields_3', Type::getType(Types::STRING), ['Length' => 255]),
            new Column('fields_4', Type::getType(Types::INTEGER)),
            new Column('fields_5', Type::getType(Types::FLOAT)),
            new Column('fields_6', Type::getType(Types::TEXT)),
            new Column('fields_7', Type::getType(Types::DATE_MUTABLE)),
            new Column('fields_8', Type::getType(Types::STRING), ['Length' => 255]),
            new Column('fields_9', Type::getType(Types::STRING), ['Length' => 255]),
        ];
        $connection->getSchemaManager()->alterTable($tableDiff);

        // This will populate CustomFieldConfig with the fixture.
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Drop columns created for this test
        $schemaManager = $this->getDatabaseConnection()->getSchemaManager();
        $table = $schemaManager->listTableDetails(CustomFields::TABLE);
        $columns = $table->getColumns();
        $tableDiff = new TableDiff(CustomFields::TABLE);
        $tableDiff->fromTable = $table;
        $tableDiff->removedColumns = [
            $columns['fields_3'],
            $columns['fields_4'],
            $columns['fields_5'],
            $columns['fields_6'],
            $columns['fields_7'],
            $columns['fields_8'],
            $columns['fields_9'],
        ];
        $schemaManager->alterTable($tableDiff);
    }

    public function testGetFields()
    {
        $fields = array(
            'TAG' => array('column' => 'tag', 'type' => 'text'),
            'Date' => array('column' => 'fields_7', 'type' => 'date'),
            'Clob' => array('column' => 'fields_6', 'type' => 'clob'),
            'Float' => array('column' => 'fields_5', 'type' => 'float'),
            'Integer' => array('column' => 'fields_4', 'type' => 'integer'),
            'Text' => array('column' => 'fields_3', 'type' => 'text'),
        );
        $this->assertEquals($fields, static::$_table->getFields());
    }

    public function addFieldProvider()
    {
        return [
            ['text', Types::STRING, $this->equalTo(255), CustomFieldConfig::INTERNALTYPE_TEXT],
            ['integer', Types::INTEGER, $this->anything(), CustomFieldConfig::INTERNALTYPE_TEXT],
            ['float', Types::FLOAT, $this->anything(), CustomFieldConfig::INTERNALTYPE_TEXT],
            ['date', Types::DATE_MUTABLE, $this->anything(), CustomFieldConfig::INTERNALTYPE_DATE],
            ['clob', Types::TEXT, $this->anything(), CustomFieldConfig::INTERNALTYPE_TEXTAREA],
        ];
    }

    /**
     * @dataProvider addFieldProvider
     */
    public function testAddField($type, $columnType, $length, $internalType)
    {
        $serviceLocator = $this->createStub(ServiceManager::class);
        $connection = $this->getDatabaseConnection();

        $customFieldConfig = new CustomFieldConfig($serviceLocator, $connection);
        $customFieldConfig->addField('New field', $type);

        // getLastInsertValue() is not portable. Query database instead. The
        // name filter is sufficient for this particular test case.
        $query = $connection->createQueryBuilder();
        $query->select('id')->from(CustomFieldConfig::TABLE)->where("name = 'New field'");
        $id = $query->execute()->fetchOne();
        $columnName = 'fields_' . $id;

        $schemaManager = $connection->getSchemaManager();
        $customFields = $schemaManager->listTableDetails(CustomFields::TABLE);
        $column = $customFields->getColumn($columnName);

        // Reset table before any assertions
        $schemaManager->dropColumn($customFields, $column);

        $this->assertEquals($columnType, $column->getType()->getName());
        $this->assertThat($column->getLength(), $length);

        $dataSet = new \PHPUnit\DbUnit\DataSet\ReplacementDataSet(
            $this->loadDataSet('AddField')
        );
        $dataSet->addFullReplacement("##ID##", $id);
        $dataSet->addFullReplacement("##TYPE##", $internalType);
        $this->assertTablesEqual(
            $dataSet->getTable('accountinfo_config'),
            $this->getConnection()->createQueryTable(
                'accountinfo_config',
                'SELECT id, name, type, account_type, show_order FROM accountinfo_config'
            )
        );
    }

    public function testAddFieldInvalidType()
    {
        $table = static::$_nada->getTable('accountinfo');
        $columns = $table->getColumns();
        try {
            static::$_table->addField('New field', 'invalid');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Invalid datatype: invalid', $e->getMessage());
            $this->assertTablesEqual(
                $this->loadDataSet()->getTable('accountinfo_config'),
                $this->getConnection()->createQueryTable(
                    'accountinfo_config',
                    'SELECT id, name, type, account_type, show_order FROM accountinfo_config'
                )
            );
            $this->assertEquals($columns, $table->getColumns());
        }
    }

    public function testAddFieldRollbackOnException()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $serviceLocator = $this->createStub(ServiceManager::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');
        $connection->expects($this->never())->method('commit');
        $connection->method('createQueryBuilder')->willThrowException(new RuntimeException('test message'));

        $table = new CustomFieldConfig($serviceLocator, $connection);
        $table->addField('name', 'text');
    }

    public function testRenameField()
    {
        static::$_table->renameField('Text', 'Renamed field');
        $this->assertTablesEqual(
            $this->loadDataSet('RenameField')->getTable('accountinfo_config'),
            $this->getConnection()->createQueryTable(
                'accountinfo_config',
                'SELECT id, name, type, account_type, show_order FROM accountinfo_config ORDER BY id'
            )
        );
    }

    public function testDeleteField()
    {
        $schemaManager = $this->getDatabaseConnection()->getSchemaManager();
        $oldColumns = $schemaManager->listTableColumns(CustomFields::TABLE);

        static::$_table->deleteField('Text');

        $newColumns = $schemaManager->listTableColumns(CustomFields::TABLE);

        // Reset table before any assertions
        $schemaManager->addColumn(CustomFields::TABLE, $oldColumns['fields_3']);

        $this->assertArrayNotHasKey('fields_3', $newColumns);
        $this->assertTablesEqual(
            $this->loadDataSet('DeleteField')->getTable('accountinfo_config'),
            $this->getConnection()->createQueryTable(
                'accountinfo_config',
                'SELECT id, name, type, account_type, show_order FROM accountinfo_config'
            )
        );
    }

    public function testDeleteFieldRollbackOnException()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $serviceLocator = $this->createStub(ServiceManager::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');
        $connection->expects($this->never())->method('commit');
        $connection->method('createQueryBuilder')->willThrowException(new RuntimeException('test message'));

        $table = new CustomFieldConfig($serviceLocator, $connection);
        $table->deleteField('name');
    }
}
