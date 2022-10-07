<?php

/**
 * Tests for the CustomFieldConfig class
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

use Database\Table\CustomFieldConfig;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Nada\Column\AbstractColumn as Column;

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
        static::$_table->delete(true);
        $customFields = static::$serviceManager->get('Database\Table\CustomFields');
        $customFields->updateSchema(true);

        // Create the columns matching the CustomFieldConfig fixture.
        $table = static::$_nada->getTable($customFields->getTable());
        $table->addColumn('fields_3', Column::TYPE_VARCHAR, 255);
        $table->addColumn('fields_4', Column::TYPE_INTEGER, 32);
        $table->addColumn('fields_5', Column::TYPE_FLOAT);
        $table->addColumn('fields_6', Column::TYPE_CLOB);
        $table->addColumn('fields_7', Column::TYPE_DATE);
        $table->addColumn('fields_8', Column::TYPE_VARCHAR, 255);
        $table->addColumn('fields_9', Column::TYPE_VARCHAR, 255);

        // This will populate CustomFieldConfig with the fixture.
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Drop columns created for this test
        $customFields = static::$_nada->getTable('accountinfo');
        $customFields->dropColumn('fields_3');
        $customFields->dropColumn('fields_4');
        $customFields->dropColumn('fields_5');
        $customFields->dropColumn('fields_6');
        $customFields->dropColumn('fields_7');
        $customFields->dropColumn('fields_8');
        $customFields->dropColumn('fields_9');
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
        return array(
            array('text', Column::TYPE_VARCHAR, $this->equalTo(255), CustomFieldConfig::INTERNALTYPE_TEXT),
            array('integer', Column::TYPE_INTEGER, $this->anything(), CustomFieldConfig::INTERNALTYPE_TEXT),
            array('float', Column::TYPE_FLOAT, $this->anything(), CustomFieldConfig::INTERNALTYPE_TEXT),
            array('date', Column::TYPE_DATE, $this->anything(), CustomFieldConfig::INTERNALTYPE_DATE),
            array('clob', Column::TYPE_CLOB, $this->anything(), CustomFieldConfig::INTERNALTYPE_TEXTAREA),
        );
    }

    /**
     * @dataProvider addFieldProvider
     */
    public function testAddField($type, $columnType, $length, $internalType)
    {
        static::$_table->addField('New field', $type);

        // getLastInsertValue() is not portable. Query database instead. The
        // name filter is sufficient for this particular test case.
        $id = static::$_table->select(array('name' => 'New field'))->current()['id'];
        $table = static::$_nada->getTable('accountinfo');
        $column = $table->getColumn('fields_' . $id);

        // Reset table before any assertions
        $table->dropColumn($column->getName());

        $this->assertEquals($columnType, $column->getDatatype());
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
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $serviceManager = $this->createMock('Laminas\ServiceManager\ServiceManager');

        $table = $this->createPartialMock(CustomFieldConfig::class, ['getSql']);
        $table->method('getSql')->willThrowException(new \RuntimeException('test message'));

        $adapterProperty = new \ReflectionProperty(get_class($table), 'adapter');
        $adapterProperty->setAccessible(true);
        $adapterProperty->setValue($table, $adapter);

        $serviceLocatorProperty = new \ReflectionProperty(get_class($table), '_serviceLocator');
        $serviceLocatorProperty->setAccessible(true);
        $serviceLocatorProperty->setValue($table, $serviceManager);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

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
        static::$_table->deleteField('Text');

        $table = static::$_nada->getTable('accountinfo');
        $columns = $table->getColumns();

        // Reset table before any assertions
        $table->addColumn('fields_3', Column::TYPE_VARCHAR, 255);

        $this->assertArrayNotHasKey('fields_3', $columns);
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
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $table = $this->createPartialMock(CustomFieldConfig::class, ['getSql']);
        $table->method('getSql')->willThrowException(new \RuntimeException('test message'));

        $adapterProperty = new \ReflectionProperty(get_class($table), 'adapter');
        $adapterProperty->setAccessible(true);
        $adapterProperty->setValue($table, $adapter);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $table->deleteField('name');
    }
}
