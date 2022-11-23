<?php

/**
 * Tests for Model\RegistryManager
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

namespace Model\Test\Registry;

use Database\Table\RegistryData;
use Database\Table\RegistryValueDefinitions;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Model\Registry\RegistryManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests for Model\RegistryManager
 */
class RegistryManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('RegistryData', 'RegistryValueDefinitions');

    public function testGetValueDefinitions()
    {
        $model = $this->getModel();
        $values = $model->getValueDefinitions();
        $this->assertInstanceOf('Laminas\Db\ResultSet\ResultSetInterface', $values);
        $values = iterator_to_array($values);
        $this->assertCount(2, $values);
        $this->assertContainsOnlyInstancesOf('Model\Registry\Value', $values);
        $this->assertEquals('name1', $values[0]['Name']);
        $this->assertEquals('name2', $values[1]['Name']);
    }

    public function testGetValueDefinition()
    {
        $model = $this->getModel();
        $value = $model->getValueDefinition('name1');
        $this->assertInstanceOf('Model\Registry\Value', $value);
        $this->assertEquals(
            array (
                'Id' => '2',
                'Name' => 'name1',
                'RootKey' => '1',
                'SubKeys' => 'sub\key1',
                'Value' => 'value1',
            ),
            $value->getArrayCopy()
        );
    }

    public function testGetValueDefinitionInvalid()
    {
        $this->expectException('Model\Registry\RuntimeException');
        $this->expectExceptionMessage('Invalid registry value name: invalid');
        $model = $this->getModel();
        $model->getValueDefinition('invalid');
    }

    public function testAddValueDefinitionWithValue()
    {
        $model = $this->getModel();
        $model->addValueDefinition('name3', \Model\Registry\Value::HKEY_USERS, 'sub\key3', 'value3');
        $this->assertTablesEqual(
            $this->loadDataSet('AddValueDefinitionWithValue')->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT name, regtree, regkey, regvalue FROM regconfig ORDER BY name'
            )
        );
    }

    public function testAddValueDefinitionWithoutValue()
    {
        $model = $this->getModel();
        $model->addValueDefinition('name3', \Model\Registry\Value::HKEY_USERS, 'sub\key3');
        $this->assertTablesEqual(
            $this->loadDataSet('AddValueDefinitionWithoutValue')->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT name, regtree, regkey, regvalue FROM regconfig ORDER BY name'
            )
        );
    }

    public function testAddValueDefinitionNameExists()
    {
        $model = $this->getModel();
        try {
            $model->addValueDefinition('name1', \Model\Registry\Value::HKEY_USERS, 'sub\key3');
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Registry\RuntimeException $e) {
            $this->assertEquals('Value already exists: name1', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
    }

    public function testAddValueDefinitionInvalidRootKey()
    {
        $model = $this->getModel();
        try {
            $model->addValueDefinition('name3', 42, 'sub\key3');
            $this->fail('Expected exception was not thrown');
        } catch (\DomainException $e) {
            $this->assertEquals('Invalid root key: 42', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
    }

    public function testAddValueDefinitionInvalidSubkey()
    {
        $model = $this->getModel();
        try {
            $model->addValueDefinition('name3', \Model\Registry\Value::HKEY_USERS, '');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Subkeys must not be empty', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
    }

    public function testRenameValueDefinition()
    {
        $model = $this->getModel();
        $model->renameValueDefinition('name1', 'new_name');
        $dataSet = $this->loadDataSet('RenameValueDefinition');
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry',
                'SELECT hardware_id, name FROM registry ORDER BY hardware_id'
            )
        );
    }

    public function testRenameValueDefinitionUnchanged()
    {
        $model = $this->getModel();
        $model->renameValueDefinition('name1', 'name1');
        $dataSet = $this->loadDataSet();
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry',
                'SELECT hardware_id, name FROM registry ORDER BY hardware_id'
            )
        );
    }

    public function testRenameValueDefinitionEmpty()
    {
        $model = $this->getModel();
        try {
            $model->renameValueDefinition('name1', '');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Name must not be empty', $e->getMessage());
        }
        $dataSet = $this->loadDataSet();
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry',
                'SELECT hardware_id, name FROM registry ORDER BY hardware_id'
            )
        );
    }

    public function testRenameValueDefinitionExists()
    {
        $model = $this->getModel();
        try {
            $model->renameValueDefinition('name1', 'name2');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Value already exists: name2', $e->getMessage());
        }
        $dataSet = $this->loadDataSet();
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry',
                'SELECT hardware_id, name FROM registry ORDER BY hardware_id'
            )
        );
    }

    public function testRenameValueDefinitionRollbackOnException()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $resultSet = $this->createMock('Laminas\Db\ResultSet\AbstractResultSet');
        $resultSet->method('count')->willReturn(0);

        /** @var MockObject|RegistryValueDefinitions */
        $registryValueDefinitions = $this->createMock('Database\Table\RegistryValueDefinitions');
        $registryValueDefinitions->method('getAdapter')->willReturn($adapter);
        $registryValueDefinitions->method('select')->willReturn($resultSet);
        $registryValueDefinitions->method('update')->willThrowException(new \RuntimeException('test message'));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $model = new RegistryManager(
            $registryValueDefinitions,
            static::$serviceManager->get(RegistryData::class)
        );
        $model->renameValueDefinition('name1', 'name2');
    }

    public function testDeleteValueDefinition()
    {
        $model = $this->getModel();
        $model->deleteValueDefinition('name2');
        $dataSet = $this->loadDataSet('DeleteValueDefinition');
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT name, regtree, regkey, regvalue FROM regconfig ORDER BY name'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry',
                'SELECT hardware_id, name FROM registry'
            )
        );
    }

    public function testDeleteValueDefinitionNonexistentId()
    {
        $model = $this->getModel();
        $model->deleteValueDefinition('invalid');
        $dataSet = $this->loadDataSet();
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig',
                'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry',
                'SELECT hardware_id, name FROM registry ORDER BY name'
            )
        );
    }

    public function testDeleteValueDefinitionRollbackOnException()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        /** @var Stub|RegistryValueDefinitions */
        $registryValueDefinitions = $this->createStub(RegistryValueDefinitions::class);
        $registryValueDefinitions->method('getAdapter')->willReturn($adapter);

        /** @var Stub|RegistryData */
        $registryData = $this->createStub(RegistryData::class);
        $registryData->method('delete')->willThrowException(new \RuntimeException('test message'));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test message');

        $model = new RegistryManager($registryValueDefinitions, $registryData);
        $model->deleteValueDefinition('name');
    }
}
