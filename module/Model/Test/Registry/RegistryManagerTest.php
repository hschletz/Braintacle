<?php
/**
 * Tests for Model\RegistryManager
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

/**
 * Tests for Model\RegistryManager
 */
class RegistryManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('RegistryData', 'RegistryValueDefinitions');

    public function testGetValueDefinitions()
    {
        $model = $this->_getModel();
        $values = $model->getValueDefinitions();
        $this->assertInstanceOf('Zend\Db\ResultSet\ResultSetInterface', $values);
        $values->buffer();
        $this->assertCount(2, $values);
        $this->assertContainsOnlyInstancesOf('Model_RegistryValue', $values);
        $this->assertEquals(
            array(
                array (
                    'Id' => '2',
                    'Name' => 'name1',
                    'RootKey' => '1',
                    'SubKeys' => 'sub\key1',
                    'ValueConfigured' => 'value1',
                ),
                array (
                    'Id' => '1',
                    'Name' => 'name2',
                    'RootKey' => '2',
                    'SubKeys' => 'sub\key2',
                    'ValueConfigured' => 'value2',
                ),
            ),
            $values->toArray()
        );
    }

    public function testGetValueDefinition()
    {
        $model = $this->_getModel();
        $value = $model->getValueDefinition(2);
        $this->assertInstanceOf('Model_RegistryValue', $value);
        $this->assertEquals(
            array (
                'Id' => '2',
                'Name' => 'name1',
                'RootKey' => '1',
                'SubKeys' => 'sub\key1',
                'ValueConfigured' => 'value1',
            ),
            $value->getArrayCopy()
        );
    }

    public function testGetValueDefinitionInvalid()
    {
        $this->setExpectedException('Model\Registry\RuntimeException', 'Invalid registry value ID: 3');
        $model = $this->_getModel();
        $model->getValueDefinition(3);
    }

    public function testAddValueDefinitionWithValue()
    {
        $model = $this->_getModel();
        $model->addValueDefinition('name3', \Model_RegistryValue::HKEY_USERS, 'sub\key3', 'value3');
        $this->assertTablesEqual(
            $this->_loadDataSet('AddValueDefinitionWithValue')->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig', 'SELECT name, regtree, regkey, regvalue FROM regconfig ORDER BY name'
            )
        );
    }

    public function testAddValueDefinitionWithoutValue()
    {
        $model = $this->_getModel();
        $model->addValueDefinition('name3', \Model_RegistryValue::HKEY_USERS, 'sub\key3');
        $this->assertTablesEqual(
            $this->_loadDataSet('AddValueDefinitionWithoutValue')->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig', 'SELECT name, regtree, regkey, regvalue FROM regconfig ORDER BY name'
            )
        );
    }

    public function testAddValueDefinitionNameExists()
    {
        $model = $this->_getModel();
        try {
            $model->addValueDefinition('name1', \Model_RegistryValue::HKEY_USERS, 'sub\key3');
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Registry\RuntimeException $e) {
            $this->assertEquals('Value already exists: name1', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->_loadDataSet()->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig', 'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
    }

    public function testAddValueDefinitionInvalidRootKey()
    {
        $model = $this->_getModel();
        try {
            $model->addValueDefinition('name3', 42, 'sub\key3');
            $this->fail('Expected exception was not thrown');
        } catch (\DomainException $e) {
            $this->assertEquals('Invalid root key: 42', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->_loadDataSet()->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig', 'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
    }

    public function testAddValueDefinitionInvalidSubkey()
    {
        $model = $this->_getModel();
        try {
            $model->addValueDefinition('name3', \Model_RegistryValue::HKEY_USERS, '');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Subkeys must not be empty', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->_loadDataSet()->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig', 'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
    }

    public function testDeleteValueDefinition()
    {
        $model = $this->_getModel();
        $model->deleteValueDefinition(1);
        $dataSet = $this->_loadDataSet('DeleteValueDefinition');
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig', 'SELECT name, regtree, regkey, regvalue FROM regconfig ORDER BY name'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry', 'SELECT hardware_id, name FROM registry'
            )
        );
    }

    public function testDeleteValueDefinitionNonexistentId()
    {
        $model = $this->_getModel();
        $model->deleteValueDefinition(3);
        $dataSet = $this->_loadDataSet();
        $this->assertTablesEqual(
            $dataSet->getTable('regconfig'),
            $this->getConnection()->createQueryTable(
                'regconfig', 'SELECT id, name, regtree, regkey, regvalue FROM regconfig ORDER BY id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('registry'),
            $this->getConnection()->createQueryTable(
                'registry', 'SELECT hardware_id, name FROM registry ORDER BY name'
            )
        );
    }
}
