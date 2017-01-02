<?php
/**
 * Tests for the CustomFieldConfig class
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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
use Nada\Column\AbstractColumn as Column;

/**
 * Tests for the CustomFieldConfig class
 */
class CustomFieldConfigTest extends AbstractTest
{
    protected static $_nada;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::$_nada = static::$serviceManager->get('Database\Nada');

        // Add columns to CustomFields table, matching config from fixture
        static::$serviceManager->get('Database\Table\CustomFields')->setSchema(true);
        $fields = static::$_nada->getTable('accountinfo');
        $fields->addColumn('fields_3', Column::TYPE_VARCHAR, 255);
        $fields->addColumn('fields_4', Column::TYPE_INTEGER, 32);
        $fields->addColumn('fields_5', Column::TYPE_FLOAT);
        $fields->addColumn('fields_6', Column::TYPE_CLOB);
        $fields->addColumn('fields_7', Column::TYPE_DATE);
        $fields->addColumn('fields_8', Column::TYPE_VARCHAR, 255);
        $fields->addColumn('fields_9', Column::TYPE_VARCHAR, 255);
    }

    public static function tearDownAfterClass()
    {
        // Drop columns created for this test
        $fields = static::$_nada->getTable('accountinfo');
        $fields->dropColumn('fields_3');
        $fields->dropColumn('fields_4');
        $fields->dropColumn('fields_5');
        $fields->dropColumn('fields_6');
        $fields->dropColumn('fields_7');
        $fields->dropColumn('fields_8');
        $fields->dropColumn('fields_9');

        parent::tearDownAfterClass();
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

        $dataSet = new \PHPUnit_Extensions_Database_DataSet_ReplacementDataSet(
            $this->_loadDataSet('AddField')
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
                $this->_loadDataSet()->getTable('accountinfo_config'),
                $this->getConnection()->createQueryTable(
                    'accountinfo_config',
                    'SELECT id, name, type, account_type, show_order FROM accountinfo_config'
                )
            );
            $this->assertEquals($columns, $table->getColumns());
        }
    }

    public function testRenameField()
    {
        static::$_table->renameField('Text', 'Renamed field');
        $this->assertTablesEqual(
            $this->_loadDataSet('RenameField')->getTable('accountinfo_config'),
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
            $this->_loadDataSet('DeleteField')->getTable('accountinfo_config'),
            $this->getConnection()->createQueryTable(
                'accountinfo_config',
                'SELECT id, name, type, account_type, show_order FROM accountinfo_config'
            )
        );
    }
}
