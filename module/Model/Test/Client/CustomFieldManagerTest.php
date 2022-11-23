<?php

/**
 * Tests for Model\Client\CustomFieldManager
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

namespace Model\Test\Client;

use Database\Table\CustomFieldConfig;
use Database\Table\CustomFields as TableCustomFields;
use InvalidArgumentException;
use Laminas\Hydrator\HydratorInterface;
use Model\Client\CustomFieldManager;
use Model\Client\CustomFields;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests for Model\Client\CustomFieldManager
 */
class CustomFieldManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('CustomFields');

    public function testGetFields()
    {
        $fieldInfo = array('field' => array('column' => 'column_name', 'type' => 'text'));

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->expects($this->once())->method('getFields')->willReturn($fieldInfo);

        $model = new CustomFieldManager($customFieldConfig, static::$serviceManager->get(TableCustomFields::class));

        // The second invocation should return a cached result.
        $fields = array('field' => 'text');
        $this->assertEquals($fields, $model->getFields());
        $this->assertEquals($fields, $model->getFields());
    }

    public function testGetColumnMap()
    {
        $fieldInfo = array('field' => array('column' => 'column_name', 'type' => 'text'));

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->expects($this->once())->method('getFields')->willReturn($fieldInfo);

        $model = new CustomFieldManager($customFieldConfig, static::$serviceManager->get(TableCustomFields::class));

        // The second invocation should return a cached result.
        $fields = array('field' => 'column_name');
        $this->assertEquals($fields, $model->getColumnMap());
        $this->assertEquals($fields, $model->getColumnMap());
    }

    public function testFieldExists()
    {
        $fields = array(
            'ä' => 'text', // Test case-insensitive non-ASCII characters
            'a/+b' => 'text', // Test escaping in regex
        );
        $model = $this->createPartialMock(CustomFieldManager::class, ['getFields']);
        $model->method('getFields')->willReturn($fields);
        $this->assertTrue($model->fieldExists('Ä'));
        $this->assertTrue($model->fieldExists('a/+b'));
        $this->assertFalse($model->fieldExists('x'));
    }

    public function testAddField()
    {
        $fieldInfo1 = array(
            'field1' => array('column' => 'column1', 'type' => 'text'),
        );
        $fieldInfo2 = array(
            'field1' => array('column' => 'column1', 'type' => 'text'),
            'field2' => array('column' => 'column2', 'type' => 'text'),
        );

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->method('getFields')->will($this->onConsecutiveCalls($fieldInfo1, $fieldInfo2));
        $customFieldConfig->expects($this->once())->method('addField')->with('field2', 'text');

        /** @var Stub|TableCustomFields */
        $customFields = $this->createStub(TableCustomFields::class);


        $model = new \Model\Client\CustomFieldManager($customFieldConfig, $customFields);
        $model->getColumnMap(); // Initialize cache
        $model->addField('field2', 'text');
        $this->assertEquals(array('field1' => 'text', 'field2' => 'text'), $model->getFields());
        // Test re-read of cached data
        $this->assertEquals(array('field1' => 'column1', 'field2' => 'column2'), $model->getColumnMap());
    }

    public function testAddFieldExists()
    {
        $fieldInfo = array(
            'field1' => array('column' => 'column1', 'type' => 'text'),
        );

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->method('getFields')->willReturn($fieldInfo);
        $customFieldConfig->expects($this->never())->method('addField');

        /** @var Stub|TableCustomFields */
        $customFields = $this->createStub(TableCustomFields::class);

        $model = new \Model\Client\CustomFieldManager($customFieldConfig, $customFields);
        try {
            $model->addField('field1', 'text');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("Column 'field1' already exists", $e->getMessage());
            $this->assertEquals(array('field1' => 'text'), $model->getFields());
        }
    }

    public function renameFieldProvider()
    {
        return array(
            array('Field1'), // Just change case of existing name
            array('new_name'), // entirely new name
        );
    }

    /**
     * @dataProvider renameFieldProvider
     */
    public function testRenameField($newName)
    {
        $fieldInfo1 = array(
            'field1' => array('column' => 'column1', 'type' => 'text'),
            'field2' => array('column' => 'column2', 'type' => 'text'),
        );
        $fieldInfo2 = array(
            $newName => array('column' => 'column1', 'type' => 'text'),
            'field2' => array('column' => 'column2', 'type' => 'text'),
        );

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->method('getFields')->will($this->onConsecutiveCalls($fieldInfo1, $fieldInfo2));
        $customFieldConfig->expects($this->once())->method('renameField')->with('field1', $newName);

        /** @var Stub|TableCustomFields */
        $customFields = $this->createStub(TableCustomFields::class);


        $model = new \Model\Client\CustomFieldManager($customFieldConfig, $customFields);
        $model->getColumnMap(); // Initialize cache
        $model->renameField('field1', $newName); // Just change case - should be valid rename
        // Test re-read of cached data
        $this->assertEquals(array($newName => 'text', 'field2' => 'text'), $model->getFields());
        $this->assertEquals(array($newName => 'column1', 'field2' => 'column2'), $model->getColumnMap());
    }

    public function renameFieldExceptionProvider()
    {
        return array(
            array('TAG', 'field2', 'System column "TAG" cannot be renamed.'),
            array('field1', 'TAG', 'Column cannot be renamed to reserved name "TAG".'),
            array('invalid', 'field2', 'Unknown column: "invalid"'),
            array('field1', 'tag', 'Column "tag" already exists.'),
        );
    }

    /**
     * @dataProvider renameFieldExceptionProvider
     */
    public function testRenameFieldRenameTag($oldName, $newName, $message)
    {
        $fieldInfo = array(
            'TAG' => array('column' => 'tag', 'type' => 'text'),
            'field1' => array('column' => 'column1', 'type' => 'text'),
        );

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->expects($this->once())->method('getFields')->willReturn($fieldInfo);
        $customFieldConfig->expects($this->never())->method('renameField');

        /** @var Stub|TableCustomFields */
        $customFields = $this->createStub(TableCustomFields::class);

        $model = new \Model\Client\CustomFieldManager($customFieldConfig, $customFields);
        $model->getFields(); // Initialize cache
        $model->getColumnMap(); // Initialize cache
        try {
            $model->renameField($oldName, $newName);
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($message, $e->getMessage());
            // Test reuse of cached data (was not reset)
            $this->assertEquals(array('TAG' => 'text', 'field1' => 'text'), $model->getFields());
            $this->assertEquals(array('TAG' => 'tag', 'field1' => 'column1'), $model->getColumnMap());
        }
    }

    public function testRenameFieldIdenticalNames()
    {
        $fieldInfo = array(
            'TAG' => array('column' => 'tag', 'type' => 'text'),
            'field1' => array('column' => 'column1', 'type' => 'text'),
        );

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->expects($this->once())->method('getFields')->willReturn($fieldInfo);
        $customFieldConfig->expects($this->never())->method('renameField');

        /** @var Stub|TableCustomFields */
        $customFields = $this->createStub(TableCustomFields::class);

        $model = new \Model\Client\CustomFieldManager($customFieldConfig, $customFields);
        $model->getFields(); // Initialize cache
        $model->getColumnMap(); // Initialize cache
        $model->renameField('field1', 'field1');
        // Test reuse of cached data (was not reset)
        $this->assertEquals(array('TAG' => 'text', 'field1' => 'text'), $model->getFields());
        $this->assertEquals(array('TAG' => 'tag', 'field1' => 'column1'), $model->getColumnMap());
    }

    public function testDeleteField()
    {
        $fieldInfo = array(
            'field1' => array('column' => 'column1', 'type' => 'text'),
            'field2' => array('column' => 'column2', 'type' => 'text'),
        );

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->expects($this->once())->method('getFields')->willReturn($fieldInfo);
        $customFieldConfig->expects($this->once())->method('deleteField')->with('field1');

        /** @var Stub|TableCustomFields */
        $customFields = $this->createStub(TableCustomFields::class);

        $model = new \Model\Client\CustomFieldManager($customFieldConfig, $customFields);
        $model->deleteField('field1');
        // Test update of cached data
        $this->assertEquals(array('field2' => 'text'), $model->getFields());
        $this->assertEquals(array('field2' => 'column2'), $model->getColumnMap());
    }

    public function deleteFieldExceptionProvider()
    {
        return array(
            array('TAG', 'Cannot delete system column "TAG".'),
            array('invalid', 'Unknown column: "invalid"'),
        );
    }

    /**
     * @dataProvider deleteFieldExceptionProvider
     */
    public function testDeleteFieldException($name, $message)
    {
        $fieldInfo = array(
            'field1' => array('column' => 'column1', 'type' => 'text'),
            'field2' => array('column' => 'column2', 'type' => 'text'),
        );

        /** @var MockObject|CustomFieldConfig */
        $customFieldConfig = $this->createMock('Database\Table\CustomFieldConfig');
        $customFieldConfig->expects($this->once())->method('getFields')->willReturn($fieldInfo);
        $customFieldConfig->expects($this->never())->method('deleteField');

        /** @var Stub|TableCustomFields */
        $customFields = $this->createStub(TableCustomFields::class);

        $model = new \Model\Client\CustomFieldManager($customFieldConfig, $customFields);
        try {
            $model->deleteField($name);
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($message, $e->getMessage());
            // Test cached data (was not updated)
            $this->assertEquals(array('field1' => 'text', 'field2' => 'text'), $model->getFields());
            $this->assertEquals(array('field1' => 'column1', 'field2' => 'column2'), $model->getColumnMap());
        }
    }

    public function testGetHydrator()
    {
        $model = $this->createPartialMock(CustomFieldManager::class, ['getFields', 'getColumnMap']);
        $model->method('getFields')->willReturn(array('TAG' => 'text', 'Date' => 'date'));
        $model->method('getColumnMap')->willReturn(array('TAG' => 'tag', 'Date' => 'fields_2'));

        $hydrator = $model->getHydrator();
        $this->assertInstanceOf(\Laminas\Hydrator\ArraySerializableHydrator::class, $hydrator);

        $namingStrategy = $hydrator->getNamingStrategy();
        $this->assertInstanceOf('Database\Hydrator\NamingStrategy\MapNamingStrategy', $namingStrategy);
        $this->assertEquals('TAG', $namingStrategy->hydrate('tag'));
        $this->assertEquals('Date', $namingStrategy->hydrate('fields_2'));
        $this->assertEquals('tag', $namingStrategy->extract('TAG'));
        $this->assertEquals('fields_2', $namingStrategy->extract('Date'));

        $this->assertInstanceOf(
            'Laminas\Hydrator\Strategy\DateTimeFormatterStrategy',
            $hydrator->getStrategy('Date')
        );
        $this->assertInstanceOf(
            'Laminas\Hydrator\Strategy\DateTimeFormatterStrategy',
            $hydrator->getStrategy('fields_2')
        );

        $this->assertFalse($hydrator->hasStrategy('TAG'));
        $this->assertFalse($hydrator->hasStrategy('tag'));
    }

    public function testRead()
    {
        $columnMap = ['hydrated_name' => 'raw_name'];

        $customFields = $this->createStub(CustomFields::class);

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('hydrate')
                 ->with(['raw_name' => 'raw_value'], $this->isInstanceOf(CustomFields::class))
                 ->willReturn($customFields);

        $model = $this->createPartialMock(CustomFieldManager::class, ['getColumnMap', 'readRaw', 'getHydrator']);
        $model->method('getColumnMap')->willReturn($columnMap);
        $model->method('readRaw')->with(42, ['raw_name'])->willReturn(['raw_name' => 'raw_value']);
        $model->method('getHydrator')->willReturn($hydrator);

        $this->assertSame($customFields, $model->read('42'));
    }

    public function testReadRaw()
    {
        $model = new CustomFieldManager(
            static::$serviceManager->get('Database\Table\CustomFieldConfig'),
            static::$serviceManager->get('Database\Table\CustomFields'),
        );

        $this->assertEquals(['tag' => 'Custom1'], $model->readRaw('1', ['tag']));
    }

    public function testReadRawInvalidId()
    {
        $model = new CustomFieldManager(
            static::$serviceManager->get('Database\Table\CustomFieldConfig'),
            static::$serviceManager->get('Database\Table\CustomFields'),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid client ID: 42');
        $model->readRaw(42, ['tag']);
    }

    public function writeProvider()
    {
        return [
            [new CustomFields(['hydrated_name' => 'hydrated_value'])],
            [['hydrated_name' => 'hydrated_value']],
        ];
    }

    /** @dataProvider writeProvider */
    public function testWrite($data)
    {
        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')->with($this->callback(function ($data) {
            return $data instanceof CustomFields and $data->getArrayCopy() == ['hydrated_name' => 'hydrated_value'];
        }))->willReturn(['raw_name' => 'raw_value']);

        $model = $this->createPartialMock(CustomFieldManager::class, ['getHydrator', 'writeRaw']);
        $model->method('getHydrator')->willReturn($hydrator);
        $model->expects($this->once())->method('writeRaw')->with(42, ['raw_name' => 'raw_value']);

        $model->write('42', $data);
    }

    public function testWriteRaw()
    {
        $model = new CustomFieldManager(
            static::$serviceManager->get('Database\Table\CustomFieldConfig'),
            static::$serviceManager->get('Database\Table\CustomFields'),
        );

        $model->writeRaw('2', ['tag' => 'new_value']);
        $this->assertTablesEqual(
            $this->loadDataSet('WriteRaw')->getTable('accountinfo'),
            $this->getConnection()->createQueryTable('accountinfo', 'SELECT hardware_id, tag FROM accountinfo')
        );
    }
}
