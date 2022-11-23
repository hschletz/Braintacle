<?php

/**
 * Tests for Model\Client\ClientManager
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

use Database\Table\AndroidInstallations;
use Database\Table\Attachments;
use Database\Table\ClientConfig;
use Database\Table\Clients;
use Database\Table\ClientsAndGroups;
use Database\Table\ClientSystemInfo;
use Database\Table\Comments;
use Database\Table\CustomFields;
use Database\Table\GroupMemberships;
use Database\Table\NetworkDevicesIdentified;
use Database\Table\NetworkDevicesScanned;
use Database\Table\NetworkInterfaces;
use Database\Table\PackageHistory;
use Database\Table\RegistryData;
use Database\Table\WindowsInstallations;
use Database\Table\WindowsProductKeys;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Model\Client\Client;
use Model\Client\ClientManager;
use Model\Client\CustomFieldManager;
use Model\Client\ItemManager;
use Model\Config;
use Model\Group\Group;
use Nada\Column\AbstractColumn as Column;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;

class ClientManagerTest extends \Model\Test\AbstractTest
{
    protected static $_tables = array(
        'ClientsAndGroups',
        'ClientSystemInfo',
        'Clients',
        'ClientConfig',
        'CustomFields',
        'Filesystems',
        'GroupMemberships',
        'NetworkDevicesIdentified',
        'NetworkDevicesScanned',
        'NetworkInterfaces',
        'Packages',
        'RegistryData',
        'Software',
        'SoftwareDefinitions',
        'SoftwareRaw',
        'WindowsProductKeys',
        'WindowsInstallations',
    );

    protected static $_customFields;

    protected $_map = array(
        'Id' => 'id',
        'AssetTag' => 'assettag',
        'BiosDate' => 'bdate',
        'BiosManufacturer' => 'bmanufacturer',
        'BiosVersion' => 'bversion',
        'IdString' => 'deviceid',
        'CpuClock' => 'processors',
        'CpuCores' => 'processorn',
        'CpuType' => 'processort',
        'DefaultGateway' => 'defaultgateway',
        'DnsServer' => 'dns',
        'InventoryDate' => 'lastdate',
        'InventoryDiff' => 'checksum',
        'IpAddress' => 'ipaddr',
        'LastContactDate' => 'lastcome',
        'Manufacturer' => 'smanufacturer',
        'Name' => 'name',
        'OsComment' => 'description',
        'OsName' => 'osname',
        'OsVersionNumber' => 'osversion',
        'OsVersionString' => 'oscomments',
        'PhysicalMemory' => 'memory',
        'ProductName' => 'smodel',
        'Serial' => 'ssn',
        'SwapMemory' => 'swap',
        'Type' => 'type',
        'UserAgent' => 'useragent',
        'UserName' => 'userid',
        'Uuid' => 'uuid',
    );

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Add columns to CustomFields table
        static::$_customFields = static::$serviceManager->get('Database\Nada')->getTable('accountinfo');
        static::$_customFields->addColumn('col_text', Column::TYPE_VARCHAR, 255);
        static::$_customFields->addColumn('col_clob', Column::TYPE_CLOB);
        static::$_customFields->addColumn('col_integer', Column::TYPE_INTEGER, 32);
        static::$_customFields->addColumn('col_float', Column::TYPE_FLOAT);
        static::$_customFields->addColumn('col_date', Column::TYPE_DATE);
    }

    public static function tearDownAfterClass(): void
    {
        // Drop columns created for this test
        static::$_customFields->dropColumn('col_text');
        static::$_customFields->dropColumn('col_clob');
        static::$_customFields->dropColumn('col_integer');
        static::$_customFields->dropColumn('col_float');
        static::$_customFields->dropColumn('col_date');

        parent::tearDownAfterClass();
    }

    public function getClientsProvider()
    {
        $client1 = array(array('id' => 1));
        $client2 = array(array('id' => 2));
        $client3 = array(array('id' => 3));
        $client12 = array(array('id' => 1), array('id' => 2));
        $client14 = array(array('id' => 1), array('id' => 4));
        $client23 = array(array('id' => 2), array('id' => 3));
        $client34 = array(array('id' => 3), array('id' => 4));
        $client123 = array(array('id' => 1), array('id' => 2), array('id' => 3));
        $client124 = array(array('id' => 1), array('id' => 2), array('id' => 4));
        $client234 = array(array('id' => 2), array('id' => 3), array('id' => 4));

        return array(
            // Minimal query
            array(
                array('Id'),
                'Id',
                'asc',
                null,
                null,
                null,
                null,
                false,
                array(
                    array('id' => 1),
                    array('id' => 2),
                    array('id' => 3),
                    array('id' => 4),
                ),
            ),
            // Property from ClientsOrGroups table, descending order
            array(
                array('Name'),
                'Name',
                'desc',
                null,
                null,
                null,
                null,
                false,
                array(
                    array('id' => 4, 'name' => 'name4'),
                    array('id' => 3, 'name' => 'name3'),
                    array('id' => 2, 'name' => 'name2'),
                    array('id' => 1, 'name' => 'name1'),
                ),
            ),
            // Property from ClientSystemInfo table
            array(
                array('Serial'),
                'Id',
                'asc',
                null,
                null,
                null,
                null,
                false,
                array(
                    array('id' => 1, 'ssn' => 'serial1'),
                    array('id' => 2, 'ssn' => 'serial2'),
                    array('id' => 3, 'ssn' => null),
                    array('id' => 4, 'ssn' => null),
                ),
            ),
            // Windows Property
            array(
                array('Windows.ProductId'),
                'Id',
                'asc',
                null,
                null,
                null,
                null,
                false,
                array(
                    array('id' => 1, 'windows_product_id' => 'product_id1'),
                    array('id' => 2, 'windows_product_id' => 'product_id2'),
                    array('id' => 3, 'windows_product_id' => null),
                    array('id' => 4, 'windows_product_id' => null),
                ),
            ),
            // No property selection, 'Id' filter
            array(
                null,
                null,
                null,
                'Id',
                2,
                null,
                null,
                false,
                array(
                    array(
                        'id' => 2,
                        'deviceid' => 'id2',
                        'name' => 'name2',
                        'processors' => 2222,
                        'processorn' => 2,
                        'processort' => 'cpu_type2',
                        'lastdate' => '2015-08-11 14:18:50',
                        'lastcome' => '2015-08-11 14:19:50',
                        'memory' => 2000,
                        'swap' => 200,
                        'dns' => '192.0.2.3',
                        'defaultgateway' => '192.0.2.4',
                        'osname' => 'os_name2',
                        'osversion' => 'os.version.number2',
                        'oscomments' => 'os_version_string2',
                        'description' => 'os_comment2',
                        'useragent' => 'user_agent2',
                        'userid' => 'user_name2',
                        'uuid' => 'uuid2',
                        'smanufacturer' => 'manufacturer2',
                        'smodel' => 'product_name2',
                        'ssn' => 'serial2',
                        'type' => 'type2',
                        'bmanufacturer' => 'bios_manufacturer2',
                        'bversion' => 'bios_version2',
                        'bdate' => 'bios.date2',
                        'assettag' => 'asset_tag2',
                        'ipaddr' => null,
                        'checksum' => '262143'
                    ),
                ),
            ),
            // String filters on "hardware" table with various operators
            array(
                array('Id'), 'Id', 'asc', 'CpuType', 'cpu_type3', 'eq', true, false, $client124
            ),
            array(
                array('Id'), 'Id', 'asc', 'DnsServer', '192.0.2.3', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'DefaultGateway', '192.0.2.4', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'Name', 'name2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'UserAgent', 'user_agent2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'OsName', 'os_name2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'OsVersionNumber', 'OS?version.*ber2', 'like', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'OsVersionString', 'version.string', 'like', true, false, $client124
            ),
            array(
                array('Id'), 'Id', 'asc', 'OsComment', 'os_comment2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'UserName', 'user_name2', 'eq', false, false, $client2
            ),
            // String filters on "bios" table with various operators
            array(
                array('Id'), 'Id', 'asc', 'AssetTag', 'asset_tag2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'BiosDate', 'bios.date', 'like', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'BiosVersion', 'bios_version1', 'eq', true, false, $client234
            ),
            array(
                array('Id'), 'Id', 'asc', 'Manufacturer', 'manufacturer.', 'like', true, false, $client234
            ),
            array(
                array('Id'), 'Id', 'asc', 'ProductName', 'product_name2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'Serial', 'serial2', 'eq', false, false, $client2
            ),
            // String filters on "clients" table with various operators
            array(
                array('Id'), 'Id', 'asc', 'CpuClock', 2222, 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'CpuClock', 3333, 'ne', false, false, $client12
            ),
            array(
                array('Id'), 'Id', 'asc', 'CpuClock', 2223, 'lt', false, false, $client12
            ),
            array(
                array('Id'), 'Id', 'asc', 'CpuClock', 2222, 'le', false, false, $client12
            ),
            array(
                array('Id'), 'Id', 'asc', 'CpuCores', 2, 'gt', false, false, $client3
            ),
            array(
                array('Id'), 'Id', 'asc', 'PhysicalMemory', 2000, 'ge', false, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'SwapMemory', 200, 'le', true, false, $client34
            ),
            // Date filters on "clients" table with various operators.
            // The "InventoryDate" operand exists twice with different time of
            // day which should be ignored.
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'eq', false, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'ne', false, false, $client1
            ),
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'lt', false, false, $client1
            ),
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'le', false, false, $client123
            ),
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'gt', false, false, array()
            ),
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'ge', false, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'LastContactDate', '2015-08-12', 'lt', false, false, $client12
            ),
            array(
                array('Id'), 'Id', 'asc', 'LastContactDate', '2015-08-11', 'le', false, false, $client12
            ),
            array(
                array('Id'), 'Id', 'asc', 'LastContactDate', '2015-08-10', 'gt', false, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'LastContactDate', '2015-08-11', 'ge', false, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'eq', true, false, $client14
            ),
            array(
                array('Id'), 'Id', 'asc', 'InventoryDate', '2015-08-11', 'ne', true, false, $client234
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'LastContactDate',
                new \DateTime('2015-08-12 12:34:56'),
                'lt',
                true,
                false,
                $client34
            ),
            // Package filters
            array(
                array('Id'), 'Id', 'asc', 'PackagePending', 'package1', null, null, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'PackageRunning', 'package2', null, null, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'PackageSuccess', 'package3', null, null, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'PackageError', 'package4', null, null, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'PackageSuccess', 'package5', null, null, false, array()
            ),
            array(
                array('Id'), 'Id', 'asc', 'PackageSuccess', 'package6', null, null, false, array()
            ),
            // "Software" filter
            array(
                array('Id'), 'Id', 'asc', 'Software', 'name2', null, null, false, $client12
            ),
            // Numeric filesystem filters
            array(
                array('Id'), 'Id', 'asc', 'Filesystem.Size', '1000', 'gt', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'Filesystem.FreeSpace', '200', 'lt', true, false, $client23
            ),
            // Custom field filters
            array(
                array('Id'), 'Id', 'asc', 'CustomFields.type_text', '2', 'like', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'CustomFields.type_clob', 'clob2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'CustomFields.type_integer', 1, 'gt', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'CustomFields.type_float', 2.2, 'lt', false, false, $client1
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'CustomFields.type_text',
                'text2',
                'eq',
                false,
                true,
                array(array('id' => 2, 'customfields_col_text' => 'text2'))
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'CustomFields.type_integer',
                2,
                'eq',
                false,
                true,
                array(array('id' => 2, 'customfields_col_integer' => 2))
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'CustomFields.type_date',
                '2015-08-02',
                'eq',
                false,
                true,
                array(array('id' => 2, 'customfields_col_date' => '2015-08-02'))
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'CustomFields.type_date',
                '2015-08-02 12:34:56',
                'eq',
                false,
                false,
                $client2
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'CustomFields.type_date',
                '2015-08-02 12:34:56',
                'lt',
                false,
                false,
                $client1
            ),
            array(
                array('Id'),
                 'Id',
                 'asc',
                 'CustomFields.type_date',
                 '2015-08-02 12:34:56',
                 'lt',
                 true,
                 false,
                 $client2
            ),
            array(
                array('Id'),
                'CustomFields.type_integer',
                'desc',
                'CustomFields.type_integer',
                1,
                'ge',
                false,
                true,
                array(
                    array('id' => 2, 'customfields_col_integer' => 2),
                    array('id' => 1, 'customfields_col_integer' => 1),
                )
            ),
            // Registry data
            array(
                array('Id'), 'Id', 'asc', 'Registry.value 1', 'content1_2', 'eq', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'Registry.value 1', '2', 'like', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'Registry.value 1', '2', 'like', true, false, $client1
            ),
            array(
                array('Id'), 'Id', 'asc', 'Registry.value 3', '2', 'like', false, false, array()
            ),
            array(
                array('Id'),
                'Registry.Content',
                'desc',
                'Registry.value 1',
                '',
                'like',
                false,
                true,
                array(
                    array('id' => 2, 'registry_content' => 'content1_2'),
                    array('id' => 1, 'registry_content' => 'content1_1'),
                )
            ),
            // String filters on items
            // Use Filesystem and Software model to ensure that they are
            // properly handled despite some special, similarly named filters.
            array(
                array('Id'), 'Id', 'asc', 'Filesystem.Filesystem', '2', 'like', false, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'Filesystem.Filesystem', '1', 'like', true, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'Software.Name', '1', 'like', false, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'Software.Name', '1', 'like', true, false, array_merge($client12, $client2)
            ),
            // Windows filters
            array(
                array('Id'), 'Id', 'asc', 'Windows.ProductId', '2', 'like', false, false, $client2
            ),
            array(
                array('Id'),
                'Windows.ProductId',
                'desc',
                'Windows.ProductId',
                '',
                'like',
                false,
                true,
                array(
                    array('id' => 2, 'windows_product_id' => 'product_id2'),
                    array('id' => 1, 'windows_product_id' => 'product_id1'),
                )
            ),
            // Single filter as array
            array(
                array('Id'), 'Id', 'asc', array('CpuCores'), array(1), array('gt'), array(false), false, $client23
            ),
            // Multiple filters
            array(
                array('Id'),
                'Id',
                'asc',
                array('CpuCores', 'CpuClock'),
                array(1, 3000),
                array('gt', 'lt'),
                array(false, false),
                false,
                $client2,
            ),
            // Add search column, column already present
            array(
                array('Id', 'Name'),
                'Id',
                'asc',
                'Name',
                'name2',
                'eq',
                false,
                true,
                array(array('id' => 2, 'name' => 'name2'))
            ),
            // Add search column (different invocations of filter method), column not present yet
            array(
                array('Id'), 'Id', 'asc', 'Name', 'name2', 'eq', false, true, array(array('id' => 2, 'name' => 'name2'))
            ),
            array(
                array('Id'), 'Id', 'asc', 'CpuCores', 2, 'eq', false, true, array(array('id' => 2, 'processorn' => 2))
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'LastContactDate',
                '2015-08-11',
                'eq',
                false,
                true,
                array(array('id' => 2, 'lastcome' => '2015-08-11 14:19:50')),
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'PackageSuccess',
                'package3',
                null,
                false,
                true,
                array(array('id' => 2, 'package_status' => 'SUCCESS')),
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'Software',
                'name1',
                null,
                false,
                true,
                array(
                    array('id' => 2, 'software_version' => 'version1a'),
                    array('id' => 3, 'software_version' => 'version1b'),
                )
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'Filesystem.Size',
                2000,
                'eq',
                false,
                true,
                array(array('id' => 2, 'filesystem_total' => 2000)),
            ),
            array(
                array('Id'),
                'Id',
                'asc',
                'Software.Name',
                'name1',
                'eq',
                false,
                true,
                array(array('id' => 2, 'software_name' => 'name1'), array('id' => 3, 'software_name' => 'name1')),
            ),
            // Add search column, search column "Name" unambiguously resolved by model prefix
            array(
                array('Name'),
                'Name',
                'desc',
                array('Software.Name', 'Software.Version'),
                array('', 'a'),
                array('like', 'like'),
                array(false, false),
                true,
                array(
                    array('id' => 2, 'name' => 'name2', 'software_name' => 'name1', 'software_version' => 'version1a'),
                    array('id' => 1, 'name' => 'name1', 'software_name' => 'name2', 'software_version' => 'version2a'),
                )
            ),
            array(
                array('Name'),
                'Software.Name',
                'desc',
                array('Software.Name', 'Software.Version'),
                array('', 'a'),
                array('like', 'like'),
                array(false, false),
                true,
                array(
                    array('id' => 1, 'name' => 'name1', 'software_name' => 'name2', 'software_version' => 'version2a'),
                    array('id' => 2, 'name' => 'name2', 'software_name' => 'name1', 'software_version' => 'version1a'),
                )
            ),
            // Add search column, first from a joined table, second and third from another joined table.
            // Ensures correct rewriting of joins.
            array(
                array('Id'),
                'Id',
                'asc',
                array('Filesystem.Size', 'Software.Name', 'Software.Version'),
                array(2000, '', 'a'),
                array('eq', 'like', 'like'),
                array(false, false, false),
                true,
                array(
                    array(
                        'id' => 2,
                        'filesystem_total' => 2000,
                        'software_name' => 'name1',
                        'software_version' => 'version1a',
                    ),
                )
            ),
        );
    }

    /**
     * @dataProvider getClientsProvider
     */
    public function testGetClients(
        $properties,
        $order,
        $direction,
        $filter,
        $search,
        $operator,
        $invert,
        $addSearchColumns,
        $expected
    ) {
        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->method('getFields')->willReturn(
            array(
                'type_text' => 'text',
                'type_clob' => 'clob',
                'type_integer' => 'integer',
                'type_float' => 'float',
                'type_date' => 'date',
                'type_invalid' => 'invalid',
            )
        );
        $customFieldManager->method('getColumnMap')->willReturn(
            array(
                'type_text' => 'col_text',
                'type_clob' => 'col_clob',
                'type_integer' => 'col_integer',
                'type_float' => 'col_float',
                'type_date' => 'col_date',
            )
        );

        $resultSetPrototype = $this->createMock('Laminas\Db\ResultSet\HydratingResultSet');
        $resultSetPrototype->expects($this->once())
                           ->method('initialize')
                           ->with(
                               $this->callback(function ($dataSource) use (&$result) {
                                $result = iterator_to_array($dataSource);
                                return true;
                               })
                           )->willReturnSelf();

        $hydrator = $this->createMock('Database\Hydrator\Clients');
        $hydrator->method('getExtractorMap')->willReturn($this->_map);
        $hydrator->method('extractName')->willReturnCallback(
            function ($name) {
                return $this->_map[$name];
            }
        );

        $clients = $this->createMock('Database\Table\Clients');
        $clients->method('getResultSetPrototype')->willReturn($resultSetPrototype);
        $clients->method('getTable')->willReturn('clients');
        $clients->method('getHydrator')->willReturn($hydrator);


        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            ['Database\Nada', static::$serviceManager->get('Database\Nada')],
            [CustomFields::class, static::$serviceManager->get(CustomFields::class)],
            [ItemManager::class, static::$serviceManager->get(ItemManager::class)],
            [RegistryData::class, static::$serviceManager->get(RegistryData::class)],
            [WindowsInstallations::class, static::$serviceManager->get(WindowsInstallations::class)],
            [Clients::class, $clients],
            [CustomFieldManager::class, $customFieldManager],
        ]);

        $model = new ClientManager($serviceLocator);

        // The mock object has a unique class name which survives the clone
        // operation and can be used to check that the result set prototype was
        // really pulled from the table gateway.
        $this->assertInstanceOf(
            get_class($resultSetPrototype),
            $model->getClients(
                $properties,
                $order,
                $direction,
                $filter,
                $search,
                $operator,
                $invert,
                $addSearchColumns
            )
        );

        $this->assertEquals($expected, $result);
    }

    public function getClientsGroupFilterProvider()
    {
        return array(
            array('MemberOf', 'Id', 'asc', 1, false, array(array('id' => 1))),
            array('MemberOf', 'Id', 'asc', 2, false, array(array('id' => 2))),
            array('ExcludedFrom', 'Id', 'asc', 1, false, array(array('id' => 2))),
            array(
                'MemberOf',
                'Membership',
                'asc',
                3,
                true,
                array(
                    array('id' => 2, 'static' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                    array('id' => 1, 'static' => \Model\Client\Client::MEMBERSHIP_ALWAYS),
                ),
            ),
            array(
                'MemberOf',
                'Membership',
                'desc',
                3,
                true,
                array(
                    array('id' => 1, 'static' => \Model\Client\Client::MEMBERSHIP_ALWAYS),
                    array('id' => 2, 'static' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                ),
            ),
        );
    }

    /**
     * @dataProvider getClientsGroupFilterProvider
     */
    public function testGetClientsGroupFilter($filter, $order, $direction, $groupId, $addColumn, $expected)
    {
        /** @var MockObject|Group */
        $group = $this->createMock('Model\Group\Group');
        $group->method('offsetGet')->with('Id')->willReturn($groupId);
        $group->expects($this->once())->method('update');

        $resultSetPrototype = $this->createMock('Laminas\Db\ResultSet\HydratingResultSet');
        $resultSetPrototype->expects($this->once())
                           ->method('initialize')
                           ->with(
                               $this->callback(function ($dataSource) use (&$result) {
                                $result = iterator_to_array($dataSource);
                                return true;
                               })
                           )->willReturnSelf();

        $hydrator = $this->createMock('Database\Hydrator\Clients');
        $hydrator->method('getExtractorMap')->willReturn($this->_map);

        $clients = $this->createMock('Database\Table\Clients');
        $clients->method('getResultSetPrototype')->willReturn($resultSetPrototype);
        $clients->method('getHydrator')->willReturn($hydrator);

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            [Clients::class, $clients],
        ]);

        $model = new ClientManager($serviceLocator);
        $model->getClients(array('Id'), $order, $direction, $filter, $group, null, null, $addColumn);
        $this->assertEquals($expected, $result);
    }

    public function getClientsDistinctProvider()
    {
        return array(
            array(
                false,
                array(
                    array('id' => 1, 'software_name' => 'name2'),
                    array('id' => 2, 'software_name' => 'name2'),
                    array('id' => 2, 'software_name' => 'name2'),
                )
            ),
            array(
                true,
                array(
                    array('id' => 1, 'software_name' => 'name2'),
                    array('id' => 2, 'software_name' => 'name2'),
                )
            ),
        );
    }

    /**
     * @dataProvider getClientsDistinctProvider
     */
    public function testGetClientsDistinct($distinct, $expected)
    {
        $resultSetPrototype = $this->createMock('Laminas\Db\ResultSet\HydratingResultSet');
        $resultSetPrototype->expects($this->once())
                           ->method('initialize')
                           ->with(
                               $this->callback(function ($dataSource) use (&$result) {
                                $result = iterator_to_array($dataSource);
                                return true;
                               })
                           )->willReturnSelf();

        $hydrator = $this->createMock('Database\Hydrator\Clients');
        $hydrator->method('getExtractorMap')->willReturn($this->_map);

        $clients = $this->createMock('Database\Table\Clients');
        $clients->method('getResultSetPrototype')->willReturn($resultSetPrototype);
        $clients->method('getHydrator')->willReturn($hydrator);

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            ['Database\Nada', static::$serviceManager->get('Database\Nada')],
            [ItemManager::class, static::$serviceManager->get(ItemManager::class)],
            [Clients::class, $clients],
        ]);

        $model = new ClientManager($serviceLocator);
        $model->getClients(array('Id'), 'Id', 'asc', 'Software.Name', 'name2', null, null, true, $distinct);
        $this->assertEquals($expected, $result);
    }

    public function getClientsExceptionsProvider()
    {
        return array(
            array('Id', 'invalid', '', 'InvalidArgumentException', 'Invalid filter: invalid'),
            array('Id', 'CustomFields.invalid', '', 'LogicException', 'Unsupported type: invalid'),
            array('Id', 'CpuClock', '=', 'DomainException', 'Invalid comparison operator: ='),
            array('Id', 'LastContactDate', '=', 'DomainException', 'Invalid comparison operator: ='),
            array('Id', 'Id', '=', 'LogicException', 'invertResult cannot be used on Id filter'),
            array(
                'Id',
                'PackagePending',
                '=',
                'LogicException',
                'invertResult cannot be used on PackagePending filter'
            ),
            array(
                'Id',
                'PackageRunning',
                '=',
                'LogicException',
                'invertResult cannot be used on PackageRunning filter'
            ),
            array(
                'Id',
                'PackageSuccess',
                '=',
                'LogicException',
                'invertResult cannot be used on PackageSuccess filter'
            ),
            array(
                'Id',
                'PackageError',
                '=',
                'LogicException',
                'invertResult cannot be used on PackageError filter'
            ),
            array(
                'Id',
                'Software',
                '=',
                'LogicException',
                'invertResult cannot be used on Software filter'
            ),
            array(
                'Id',
                'MemberOf',
                '=',
                'LogicException',
                'invertResult cannot be used on MemberOf filter'
            ),
            array(
                'Id',
                'ExcludedFrom',
                '=',
                'LogicException',
                'invertResult cannot be used on ExcludedFrom filter'
            ),
            array(
                'Software',
                null,
                null,
                'InvalidArgumentException',
                'Invalid order: Software'
            ),
            array(
                'Software.',
                null,
                null,
                'InvalidArgumentException',
                'Invalid order: Software'
            ),
        );
    }

    /**
     * @dataProvider getClientsExceptionsProvider
     */
    public function testGetClientsExceptions($order, $filter, $operator, $exceptionType, $message)
    {
        $this->expectException($exceptionType, $message);

        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->method('getFields')->willReturn(
            array(
                'invalid' => 'invalid',
            )
        );

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            ['Database\Nada', static::$serviceManager->get('Database\Nada')],
            [Clients::class, static::$serviceManager->get(Clients::class)],
            [CustomFieldManager::class, $customFieldManager],
        ]);

        $model = new ClientManager($serviceLocator);
        $model->getClients(array('Id'), $order, 'asc', $filter, '2015-08-17', $operator, true);
    }

    public function testGetClientSelect()
    {
        $model = $this->getModel();
        $result = $model->getClients(null, null, 'asc', null, null, null, null, true, false, false);
        $this->assertInstanceOf('Laminas\Db\Sql\Select', $result);
    }

    public function testGetClient()
    {
        $resultSet = $this->createMock('Laminas\Db\ResultSet\HydratingResultSet');
        $resultSet->method('current')->willReturn('client');
        $resultSet->method('count')->willReturn(1);

        $model = $this->createPartialMock(ClientManager::class, ['getClients']);
        $model->method('getClients')->with(null, null, null, 'Id', 42)->willReturn($resultSet);
        $this->assertEquals('client', $model->getClient(42));
    }

    public function testGetClientInvalidId()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Invalid client ID: 42');

        $resultSet = $this->createMock('Laminas\Db\ResultSet\HydratingResultSet');
        $resultSet->method('count')->willReturn(0);

        $model = $this->createPartialMock(ClientManager::class, ['getClients']);
        $model->method('getClients')->with(null, null, null, 'Id', 42)->willReturn($resultSet);
        $model->getClient(42);
    }

    public function deleteClientNoDeleteInterfacesProvider()
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection1->expects($this->once())->method('beginTransaction');
        $connection1->expects($this->once())->method('commit');
        $connection1->expects($this->never())->method('rollback');

        $connection2 = $this->createMock(ConnectionInterface::class);
        $connection2->expects($this->once())->method('beginTransaction')->willThrowException(new \RuntimeException());
        $connection2->expects($this->never())->method('commit');
        $connection2->expects($this->never())->method('rollback');

        return array(
            array($connection1),
            array($connection2),
        );
    }

    /**
     * @dataProvider deleteClientNoDeleteInterfacesProvider
     */
    public function testDeleteClientNoDeleteInterfaces($connection)
    {
        /** @var MockObject|Client */
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('lock')->willReturn(true);
        $client->expects($this->once())->method('offsetGet')->with('Id')->willReturn(42);
        $client->expects($this->once())->method('unlock');

        $driver = $this->createMock(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $androidInstallations = $this->createMock('Database\Table\AndroidInstallations');
        $androidInstallations->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $clientSystemInfo = $this->createMock('Database\Table\ClientSystemInfo');
        $clientSystemInfo->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $comments = $this->createMock('Database\Table\Comments');
        $comments->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $customFields = $this->createMock('Database\Table\CustomFields');
        $customFields->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $packageHistory = $this->createMock('Database\Table\PackageHistory');
        $packageHistory->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $windowsProductKeys = $this->createMock('Database\Table\WindowsProductKeys');
        $windowsProductKeys->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $groupMemberships = $this->createMock('Database\Table\GroupMemberships');
        $groupMemberships->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->once())->method('delete')->with(array('hardware_id' => 42));

        $attachments = $this->createMock('Database\Table\Attachments');
        $attachments->expects($this->once())->method('delete')->with(
            array('id_dde' => 42, 'table_name' => \Database\Table\Attachments::OBJECT_TYPE_CLIENT)
        );

        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())->method('deleteItems')->with(42);

        $clientsAndGroups = $this->createMock('Database\Table\ClientsAndGroups');
        $clientsAndGroups->expects($this->once())->method('delete')->with(array('id' => 42));

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            ['Db', $adapter],
            [AndroidInstallations::class, $androidInstallations],
            [Attachments::class, $attachments],
            [ClientConfig::class, $clientConfig],
            [ClientsAndGroups::class, $clientsAndGroups],
            [ClientSystemInfo::class, $clientSystemInfo],
            [Comments::class, $comments],
            [CustomFields::class, $customFields],
            [GroupMemberships::class, $groupMemberships],
            [ItemManager::class, $itemManager],
            [PackageHistory::class, $packageHistory],
            [WindowsProductKeys::class, $windowsProductKeys],
        ]);

        $clientManager = new ClientManager($serviceLocator);
        $clientManager->deleteClient($client, false);
    }

    public function testDeleteClientDeleteInterfaces()
    {
        /** @var MockObject|Client */
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('lock')->willReturn(true);
        $client->expects($this->once())->method('offsetGet')->with('Id')->willReturn(4);
        $client->expects($this->once())->method('unlock');

        $androidInstallations = $this->createMock('Database\Table\AndroidInstallations');
        $androidInstallations->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $clientSystemInfo = $this->createMock(ClientSystemInfo::class);
        $clientSystemInfo->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $customFields = $this->createMock(CustomFields::class);
        $customFields->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $packageHistory = $this->createMock(PackageHistory::class);
        $packageHistory->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $windowsProductKeys = $this->createMock(WindowsProductKeys::class);
        $windowsProductKeys->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->expects($this->once())->method('delete')->with(array('hardware_id' => 4));

        $attachments = $this->createMock('Database\Table\Attachments');
        $attachments->expects($this->once())->method('delete')->with(
            array('id_dde' => 4, 'table_name' => \Database\Table\Attachments::OBJECT_TYPE_CLIENT)
        );

        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())->method('deleteItems')->with(4);

        $clientsAndGroups = $this->createMock(ClientsAndGroups::class);
        $clientsAndGroups->expects($this->once())->method('delete')->with(array('id' => 4));

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            ['Db', static::$serviceManager->get('Db')],
            [NetworkDevicesIdentified::class, static::$serviceManager->get(NetworkDevicesIdentified::class)],
            [NetworkDevicesScanned::class, static::$serviceManager->get(NetworkDevicesScanned::class)],
            [NetworkInterfaces::class, static::$serviceManager->get(NetworkInterfaces::class)],
            [AndroidInstallations::class, $androidInstallations],
            [Attachments::class, $attachments],
            [ClientConfig::class, $clientConfig],
            [ClientsAndGroups::class, $clientsAndGroups],
            [ClientSystemInfo::class, $clientSystemInfo],
            [Comments::class, $comments],
            [CustomFields::class, $customFields],
            [GroupMemberships::class, $groupMemberships],
            [ItemManager::class, $itemManager],
            [PackageHistory::class, $packageHistory],
            [WindowsProductKeys::class, $windowsProductKeys],
        ]);

        $clientManager = new ClientManager($serviceLocator);
        $clientManager->deleteClient($client, true);

        $dataSet = $this->loadDataSet('DeleteClientDeleteInterfaces');
        $connection = $this->getConnection();
        $this->assertTablesEqual(
            $dataSet->getTable('netmap'),
            $connection->createQueryTable('netmap', 'SELECT mac FROM netmap ORDER BY mac')
        );
        $this->assertTablesEqual(
            $dataSet->getTable('network_devices'),
            $connection->createQueryTable('network_devices', 'SELECT macaddr FROM network_devices ORDER BY macaddr')
        );
    }

    public function testDeleteClientLockingFailure()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Could not lock client for deletion');

        /** @var MockObject|Client */
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('lock')->willReturn(false);
        $client->expects($this->never())->method('unlock');

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->expects($this->never())->method('get');

        $clientManager = new ClientManager($serviceLocator);
        $clientManager->deleteClient($client, false);
    }


    public function deleteClientExceptionProvider()
    {
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection1->expects($this->once())->method('beginTransaction');
        $connection1->expects($this->never())->method('commit');
        $connection1->expects($this->once())->method('rollback');

        $connection2 = $this->createMock(ConnectionInterface::class);
        $connection2->expects($this->once())->method('beginTransaction')->willThrowException(new \RuntimeException());
        $connection2->expects($this->never())->method('commit');
        $connection2->expects($this->never())->method('rollback');

        return array(
            array($connection1),
            array($connection2),
        );
    }

    /**
     * @dataProvider deleteClientExceptionProvider
     */
    public function testDeleteClientException($connection)
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('message');

        /** @var MockObject|Client */
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('lock')->willReturn(true);
        $client->expects($this->once())->method('offsetGet')->with('Id')->willReturn(42);
        $client->expects($this->once())->method('unlock');

        $driver = $this->createMock(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\Adapter');
        $adapter->method('getDriver')->willReturn($driver);

        $androidInstallations = $this->createMock(AndroidInstallations::class);
        $androidInstallations->method('delete')->willThrowException(new \RuntimeException('message'));

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            ['Db', $adapter],
            [AndroidInstallations::class, $androidInstallations],
        ]);

        $clientManager = new ClientManager($serviceLocator);
        $clientManager->deleteClient($client, false);
    }

    public function testImportFile()
    {
        $content = "testUploadFile\nline1\nline2\n";
        $root = vfsstream::setup('root');
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($root)->url();
        $model = $this->createPartialMock(ClientManager::class, ['importClient']);
        $model->expects($this->once())
              ->method('importClient')
              ->with($content)
              ->willReturn('response');
        $this->assertEquals('response', $model->importFile($url));
    }

    public function testImportClientSuccess()
    {
        $uri = 'http://example.net/server';
        $content = "testUploadFile\nline1\nline2\n";

        $response = $this->createStub(\Laminas\Http\Response::class);
        $response->method('isSuccess')->willReturn(true);

        $httpClient = $this->createMock(\Laminas\Http\Client::class);
        $httpClient->expects($this->once())->method('setOptions')->with([
            'strictredirects' => true, // required for POST requests
            'useragent' => 'Braintacle_local_upload', // Substring 'local' required for correct server operation
        ])->willReturnSelf();
        $httpClient->expects($this->once())->method('setMethod')->with('POST')->willReturnSelf();
        $httpClient->expects($this->once())->method('setUri')->with($uri)->willReturnSelf();
        $httpClient->expects($this->once())
                   ->method('setHeaders')
                   ->with(['Content-Type' => 'application/x-compress'])
                   ->willReturnSelf();
        $httpClient->expects($this->once())->method('setRawBody')->with($content)->willReturnSelf();
        $httpClient->expects($this->once())->method('send')->willReturn($response);

        $config = $this->createMock('Model\Config');
        $config->method('__get')->with('communicationServerUri')->willReturn($uri);

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            [Config::class, $config],
            ['Library\HttpClient', $httpClient],
        ]);

        $model = new ClientManager($serviceLocator);
        $model->importClient($content);
    }

    public function testImportClientHttpError()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage(
            "Upload error. Server http://example.net/server responded with error 418: I'm a teapot"
        );

        $response = $this->createStub(\Laminas\Http\Response::class);
        $response->method('isSuccess')->willReturn(false);
        $response->method('getStatusCode')->willReturn(418);
        $response->method('getReasonPhrase')->willReturn("I'm a teapot");

        $httpClient = $this->createStub(\Laminas\Http\Client::class);
        $httpClient->method('setOptions')->willReturnSelf();
        $httpClient->method('setMethod')->willReturnSelf();
        $httpClient->method('setUri')->willReturnSelf();
        $httpClient->method('setHeaders')->willReturnSelf();
        $httpClient->method('setRawBody')->willReturnSelf();
        $httpClient->method('send')->willReturn($response);

        $config = $this->createMock('Model\Config');
        $config->method('__get')->with('communicationServerUri')->willReturn('http://example.net/server');

        /** @var MockObject|ServiceLocatorInterface */
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->willReturnMap([
            [Config::class, $config],
            ['Library\HttpClient', $httpClient]
        ]);

        $model = new ClientManager($serviceLocator);
        $model->importClient('content');
    }
}
