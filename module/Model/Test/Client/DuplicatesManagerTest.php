<?php
/**
 * Tests for Model\Client\DuplicatesManager
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

namespace Model\Test\Client;

/**
 * Tests for Model\Client\DuplicatesManager
 */
class DuplicatesManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array(
        'AudioDevices',
        'ClientConfig',
        'ClientsAndGroups',
        'ClientSystemInfo',
        'Config',
        'CustomFieldConfig',
        'CustomFields',
        'DuplicateAssetTags',
        'DuplicateMacAddresses',
        'DuplicateSerials',
        'GroupInfo',
        'GroupMemberships',
        'Locks',
        'Modems',
        'NetworkInterfaces',
        'Printers',
        'RegistryData',
        'WindowsInstallations',
    );

    /** {@inheritdoc} */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Provide mock tables which are referenced by \Model_Computer::delete().
        // They don't need any content and the mocked structure is reduced to a minimum.
        $database = \Library\Application::getService('Database\Nada');
        $mockTables = array(
            'controllers',
            'download_history',
            'download_servers',
            'drives',
            'inputs',
            'itmgmt_comments',
            'javainfo',
            'journallog',
            'memories',
            'monitors',
            'ports',
            'slots',
            'softwares',
            'storages',
            'videos',
            'virtualmachines',
        );
        $columns = array($database->createColumn('hardware_id', \Nada::DATATYPE_INTEGER));
        foreach ($mockTables as $table) {
            $database->createTable($table, $columns, 'hardware_id');
        }

        $columns = array(
            $database->createColumn('id_dde', \Nada::DATATYPE_INTEGER),
            $database->createColumn('table_name', \Nada::DATATYPE_VARCHAR, 255),
        );
        $database->createTable('temp_files', $columns, 'id_dde');
    }

    /**
     * Common assertions for testMerge*()
     *
     * @param string $dataSetName Name of the dataset file to compare with merged content
     */
    public function assertTablesMerged($dataSetName)
    {
        $dataSet = $this->_loadDataSet($dataSetName);
        $connection = $this->getConnection();

        // Test only tables where data may get merged.
        // We rely on \Model_Computer::delete() to clean other tables as well.
        $this->assertTablesEqual(
            $dataSet->getTable('hardware'),
            $connection->createQueryTable(
                'hardware',
                'SELECT id, deviceid, name, lastcome FROM hardware WHERE deviceid != \'_SYSTEMGROUP_\' ORDER BY id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('accountinfo'),
            $connection->createQueryTable(
                'accountinfo',
                'SELECT hardware_id, tag FROM accountinfo ORDER BY hardware_id'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('devices'),
            $connection->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue FROM devices ORDER BY hardware_id, name, ivalue'
            )
        );
        $this->assertTablesEqual(
            $dataSet->getTable('groups_cache'),
            $connection->createQueryTable(
                'groups_cache',
                'SELECT hardware_id, group_id, static FROM groups_cache ORDER BY hardware_id, group_id'
            )
        );
    }

    /**
     * Tests for count()
     */
    public function testCount()
    {
        $duplicates = $this->_getModel();

        // These criteria are initially allowed duplicate.
        $this->assertEquals(0, $duplicates->count('MacAddress'));
        $this->assertEquals(0, $duplicates->count('Serial'));
        $this->assertEquals(0, $duplicates->count('AssetTag'));

        // Duplicate names are always counted.
        $this->assertEquals(2, $duplicates->count('Name'));

        // Clear list of allowed duplicate values and re-check.
        \Library\Application::getService('Database\Table\DuplicateMacAddresses')->delete(true);
        \Library\Application::getService('Database\Table\DuplicateSerials')->delete(true);
        \Library\Application::getService('Database\Table\DuplicateAssetTags')->delete(true);
        $this->assertEquals(2, $duplicates->count('MacAddress'));
        $this->assertEquals(2, $duplicates->count('Serial'));
        $this->assertEquals(2, $duplicates->count('AssetTag'));

        // Test invalid criteria
        $this->setExpectedException('InvalidArgumentException');
        $duplicates->count('invalid');
    }

    /**
     * Tests for find()
     */
    public function testFind()
    {
        $duplicates = $this->_getModel();

        $expectedResult = array(
            array (
                'Id' => '2',
                'Name' => 'Name2',
                'LastContactDate' => new \Zend_Date('2013-12-23 13:02:33'),
                'Serial' => 'duplicate',
                'AssetTag' => 'duplicate',
                'NetworkInterface.MacAddress' => new \Library\MacAddress('00:00:5E:00:53:01'),
            ),
            array (
                'Id' => '3',
                'Name' => 'Name2',
                'LastContactDate' => new \Zend_Date('2013-12-23 13:03:33'),
                'Serial' => 'duplicate',
                'AssetTag' => 'duplicate',
                'NetworkInterface.MacAddress' => new \Library\MacAddress('00:00:5E:00:53:01'),
            ),
        );

        // These criteria are initially allowed duplicate.
        $this->assertCount(0, $duplicates->find('MacAddress'));
        $this->assertCount(0, $duplicates->find('Serial'));
        $this->assertCount(0, $duplicates->find('AssetTag'));

        // Duplicate names are always counted.
        $this->assertEquals($expectedResult, $duplicates->find('Name')->toArray());

        // Clear list of allowed duplicate values and re-check.
        \Library\Application::getService('Database\Table\DuplicateMacAddresses')->delete(true);
        \Library\Application::getService('Database\Table\DuplicateSerials')->delete(true);
        \Library\Application::getService('Database\Table\DuplicateAssetTags')->delete(true);
        $this->assertEquals($expectedResult, $duplicates->find('MacAddress')->toArray());
        $this->assertEquals($expectedResult, $duplicates->find('Serial')->toArray());
        $this->assertEquals($expectedResult, $duplicates->find('AssetTag')->toArray());

        // Test sorting
        $this->assertEquals(
            array_reverse($expectedResult),
            $duplicates->find('Name', 'Id', 'desc')->toArray()
        );

        // Test secondary sorting
        $this->assertEquals(
            $expectedResult,
            $duplicates->find('Name', 'Name')->toArray()
        );

        // Test invalid criteria
        $this->setExpectedException('InvalidArgumentException');
        $duplicates->count('invalid');
    }

    /**
     * Test merge() with less than 2 computers (no action is taken)
     */
    public function testMergeNone()
    {
        $mergeIds = array(2, 2); // Test deduplication of IDs

        $this->_getModel()->merge($mergeIds, true, true, true);
        $this->assertTablesMerged('MergeNone');
    }

    /**
     * Test merge() with locking error (operation should abort)
     */
    public function testMergeLockingError()
    {
        $mergeIds = array(2, 3);

        $computer = clone \Library\Application::getService('Model\Computer\Computer');
        $computer->fetchById(3);
        $computer->lock();

        $this->setExpectedException('RuntimeException');
        $this->_getModel()->merge($mergeIds, true, true, true);
        $this->assertTablesMerged('MergeNone');
    }

    /**
     * Test merge() with no extra merging (just delete duplicate)
     */
    public function testMergeBasic()
    {
        $mergeIds = array(2, 2, 3, 3); // Test deduplication of IDs

        $this->_getModel()->merge($mergeIds, false, false, false); // Don't merge anything
        $this->assertTablesMerged('MergeBasic');
    }

    /**
     * Test merge() with reverse specification of IDs (ensure independence from ID ordering)
     */
    public function testMergeReverse()
    {
        $mergeIds = array(3, 3, 2, 2); // Test deduplication of IDs

        $this->_getModel()->merge($mergeIds, false, false, false);

        $dataSet = $this->_loadDataSet('MergeBasic');
        $connection = $this->getConnection();

        // The result should be the same as with testMergeBasic(). Test only computers to confirm.
        $this->assertTablesEqual(
            $dataSet->getTable('hardware'),
            $connection->createQueryTable(
                'hardware',
                'SELECT id, deviceid, name, lastcome FROM hardware WHERE deviceid != \'_SYSTEMGROUP_\' ORDER BY id'
            )
        );
    }

    /**
     * Test merge() with merging of custom fields
     */
    public function testMergeCustomFields()
    {
        $this->_getModel()->merge(array(2, 3), true, false, false);
        $this->assertTablesMerged('MergeCustomFields');
    }

    /**
     * Test merge() with merging of group memberships
     */
    public function testMergeGroups()
    {
        $this->_getModel()->merge(array(2, 3), false, true, false);
        $this->assertTablesMerged('MergeGroups');
    }

    /**
     * Test merge() with merging of package assignments
     */
    public function testMergePackages()
    {
        $this->_getModel()->merge(array(2, 3), false, false, true);
        $this->assertTablesMerged('MergePackages');
    }

    /**
     * Tests for allow()
     */
    public function testAllow()
    {
        $dataSet = $this->_loadDataSet('Allow');
        $connection = $this->getConnection();
        $duplicates = $this->_getModel();

        // New entry
        $duplicates->allow('MacAddress', '00:00:5E:00:53:00');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_macaddresses'),
            $connection->createQueryTable(
                'blacklist_macaddresses',
                'SELECT macaddress FROM blacklist_macaddresses ORDER BY macaddress'
            )
        );
        // Existing entry should produce no error and no duplicate
        $duplicates->allow('MacAddress', '00:00:5E:00:53:01');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_macaddresses'),
            $connection->createQueryTable(
                'blacklist_macaddresses',
                'SELECT macaddress FROM blacklist_macaddresses ORDER BY macaddress'
            )
        );

        // New entry
        $duplicates->allow('Serial', 'test');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_serials'),
            $connection->createQueryTable(
                'blacklist_serials',
                'SELECT serial FROM blacklist_serials ORDER BY serial'
            )
        );
        // Existing entry should produce no error and no duplicate
        $duplicates->allow('Serial', 'duplicate');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_serials'),
            $connection->createQueryTable(
                'blacklist_serials',
                'SELECT serial FROM blacklist_serials ORDER BY serial'
            )
        );

        // New entry
        $duplicates->allow('AssetTag', 'test');
        $this->assertTablesEqual(
            $dataSet->getTable('braintacle_blacklist_assettags'),
            $connection->createQueryTable(
                'braintacle_blacklist_assettags',
                'SELECT assettag FROM braintacle_blacklist_assettags ORDER BY assettag'
            )
        );
        // Existing entry should produce no error and no duplicate
        $duplicates->allow('AssetTag', 'duplicate');
        $this->assertTablesEqual(
            $dataSet->getTable('braintacle_blacklist_assettags'),
            $connection->createQueryTable(
                'braintacle_blacklist_assettags',
                'SELECT assettag FROM braintacle_blacklist_assettags ORDER BY assettag'
            )
        );

        $this->setExpectedException('InvalidArgumentException');
        $duplicates->allow('invalid', 'test');
    }
}
