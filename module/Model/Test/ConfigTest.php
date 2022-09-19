<?php

/**
 * Tests for Model\Config
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

namespace Model\Test;

/**
 * Tests for Model\Config
 */
class ConfigTest extends AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Config');

    /**
     * Tests for getDbIdentifier()
     */
    public function testGetDbIdentifier()
    {
        $model = $this->getModel();
        $this->assertEquals('FREQUENCY', $model->getDbIdentifier('inventoryInterval'));
        $this->expectException('InvalidArgumentException');
        $model->getDbIdentifier('invalid');
    }

    /**
     * Tests for __get()
     */
    public function testMagicGet()
    {
        $config = $this->getModel();

        // Test populated ivalue and tvalue options
        $this->assertEquals(42, $config->inventoryInterval);
        $this->assertEquals('/example/package/path', $config->packagePath);
        // Test default for unpopulated option
        $this->assertSame(12, $config->contactInterval);
        // Test invalid option
        $this->expectException('InvalidArgumentException');
        $config->invalid;
    }

    public function testMagicSet()
    {
        $config = $this->getModel();

        $config->inventoryInterval = 42; // unchanged
        $config->contactInterval = 10; // new
        $config->packagePath = '/other/package/path'; // updated
        $config->inspectRegistry = true; // ivalue true, updated
        $config->saveRawData = false; // ivalue false, updated
        $config->sessionRequired = true; // ivalue true, new
        $config->trustedNetworksOnly = false; // ivalue false, new

        $dataSet = $this->loadDataSet('MagicSet');
        $this->assertTablesEqual(
            $dataSet->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testMagicSetInvalidOption()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid option: invalid');
        $this->getModel()->invalid = 0;
    }

    public function testMagicSetInvalidValue()
    {
        $this->expectException(
            'InvalidArgumentException',
            'Tried to set non-integer value "invalid" to integer option "inventoryInterval"'
        );
        $this->getModel()->inventoryInterval = 'invalid';
    }

    public function testSetOptionsBooleanFalse()
    {
        $options = array(
            'defaultWarn' => false, // String storage, default 0
            'defaultMergeGroups' => false, // String storage, default 1
            'packageDeployment' => false, // Integer storage, default 0
            'scanSnmp' => false, // Integer storage, default 1
        );
        $this->getModel()->setOptions($options);
        $this->assertTablesEqual(
            $this->loadDataSet('SetOptionsFalse')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetOptionsBooleanTrue()
    {
        $options = array(
            'defaultWarn' => true, // String storage, default 0
            'defaultMergeGroups' => true, // String storage, default 1
            'packageDeployment' => true, // Integer storage, default 0
            'scanSnmp' => true, // Integer storage, default 1
        );
        $this->getModel()->setOptions($options);
        $this->assertTablesEqual(
            $this->loadDataSet('SetOptionsTrue')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetOptionsInteger0()
    {
        $options = array(
            'defaultWarn' => 0, // String storage, default 0
            'defaultMergeGroups' => 0, // String storage, default 1
            'packageDeployment' => 0, // Integer storage, default 0
            'scanSnmp' => 0, // Integer storage, default 1
        );
        $this->getModel()->setOptions($options);
        $this->assertTablesEqual(
            $this->loadDataSet('SetOptionsFalse')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetOptionsInteger1()
    {
        $options = array(
            'defaultWarn' => 1, // String storage, default 0
            'defaultMergeGroups' => 1, // String storage, default 1
            'packageDeployment' => 1, // Integer storage, default 0
            'scanSnmp' => 1, // Integer storage, default 1
        );
        $this->getModel()->setOptions($options);
        $this->assertTablesEqual(
            $this->loadDataSet('SetOptionsTrue')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetOptionsString0()
    {
        $options = array(
            'defaultWarn' => '0', // String storage, default 0
            'defaultMergeGroups' => '0', // String storage, default 1
            'packageDeployment' => '0', // Integer storage, default 0
            'scanSnmp' => '0', // Integer storage, default 1
        );
        $this->getModel()->setOptions($options);
        $this->assertTablesEqual(
            $this->loadDataSet('SetOptionsFalse')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetOptionsString1()
    {
        $options = array(
            'defaultWarn' => '1', // String storage, default 0
            'defaultMergeGroups' => '1', // String storage, default 1
            'packageDeployment' => '1', // Integer storage, default 0
            'scanSnmp' => '1', // Integer storage, default 1
        );
        $this->getModel()->setOptions($options);
        $this->assertTablesEqual(
            $this->loadDataSet('SetOptionsTrue')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetOptionsAcceptsIterator()
    {
        $options = array(
            'defaultWarn' => '1',
            'defaultMergeGroups' => '1',
            'packageDeployment' => '1',
            'scanSnmp' => '1',
        );
        $this->getModel()->setOptions(new \IteratorIterator(new \ArrayIterator($options)));
        $this->assertTablesEqual(
            $this->loadDataSet('SetOptionsTrue')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testAutoMergeDuplicatesTrue()
    {
        $this->getModel()->autoMergeDuplicates = '1';
        $this->assertTablesEqual(
            $this->loadDataSet('AutoMergeDuplicatesTrue')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
        // Read value from new clone to test conversion from database content
        $this->assertSame(1, $this->getModel()->autoMergeDuplicates);
    }

    public function testAutoMergeDuplicatesFalse()
    {
        $this->getModel()->autoMergeDuplicates = '0';
        $this->assertTablesEqual(
            $this->loadDataSet('AutoMergeDuplicatesFalse')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
        // Read value from new clone to test conversion from database content
        $this->assertSame(0, $this->getModel()->autoMergeDuplicates);
    }
}
