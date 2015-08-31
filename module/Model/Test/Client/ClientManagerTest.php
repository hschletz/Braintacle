<?php
/**
 * Tests for Model\Client\ClientManager
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
        'Packages',
        'RegistryData',
        'Software',
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
        'ClientId' => 'deviceid',
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
        'Model' => 'smodel',
        'Name' => 'name',
        'OcsAgent' => 'useragent',
        'OsComment' => 'description',
        'OsName' => 'osname',
        'OsVersionNumber' => 'osversion',
        'OsVersionString' => 'oscomments',
        'PhysicalMemory' => 'memory',
        'Serial' => 'ssn',
        'SwapMemory' => 'swap',
        'Type' => 'type',
        'UserName' => 'userid',
        'Uuid' => 'uuid',
    );

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Add columns to CustomFields table
        static::$_customFields = \Library\Application::getService('Database\Nada')->getTable('accountinfo');
        static::$_customFields->addColumn('col_text', \Nada::DATATYPE_VARCHAR, 255);
        static::$_customFields->addColumn('col_clob', \Nada::DATATYPE_CLOB);
        static::$_customFields->addColumn('col_integer', \Nada::DATATYPE_INTEGER, 32);
        static::$_customFields->addColumn('col_float', \Nada::DATATYPE_FLOAT);
        static::$_customFields->addColumn('col_date', \Nada::DATATYPE_DATE);
    }

    public static function tearDownAfterClass()
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
                        'useragent' => 'user_agent2',
                        'osname' => 'os_name2',
                        'osversion' => 'os.version.number2',
                        'oscomments' => 'os_version_string2',
                        'description' => 'os_comment2',
                        'userid' => 'user_name2',
                        'uuid' => 'uuid2',
                        'smanufacturer' => 'manufacturer2',
                        'smodel' => 'model2',
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
                array('Id'), 'Id', 'asc', 'OcsAgent', 'user_agent2', 'eq', false, false, $client2
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
                array('Id'), 'Id', 'asc', 'Model', 'model2', 'eq', false, false, $client2
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
                array('Id'), 'Id', 'asc', 'PackageNonnotified', 'package1', null, null, false, $client2
            ),
            array(
                array('Id'), 'Id', 'asc', 'PackageNotified', 'package2', null, null, false, $client2
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
                array('Id'), 'Id', 'asc', 'Software', 'name1', null, null, false, $client12
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
                'Id',
                'asc',
                'Registry.value 2',
                'content2_2',
                'eq',
                false,
                true,
                array(array('id' => 2, 'registry_content' => 'content2_2'))
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
                array('Id'), 'Id', 'asc', 'Software.Name', '2', 'like', false, false, $client23
            ),
            array(
                array('Id'), 'Id', 'asc', 'Software.Name', '2', 'like', true, false, array_merge($client12, $client2)
            ),
            // Windows filters
            array(
                array('Id'), 'Id', 'asc', 'Windows.ProductId', '2', 'like', false, false, $client2
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
                'name2',
                null,
                false,
                true,
                array(
                    array('id' => 2, 'software_version' => 'version2a'),
                    array('id' => 3, 'software_version' => 'version2b'),
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
                'name2',
                'eq',
                false,
                true,
                array(array('id' => 2, 'software_name' => 'name2'), array('id' => 3, 'software_name' => 'name2')),
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
    )
    {
        $customFieldManager = $this->getMockBuilder('Model\Client\CustomFieldManager')
                                   ->disableOriginalConstructor()
                                   ->getMock();
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

        $resultSetPrototype = $this->getMock('Zend\Db\ResultSet\HydratingResultSet');
        $resultSetPrototype->expects($this->once())
                           ->method('initialize')
                           ->with(
                               $this->callback(
                                   function($dataSource) use(&$result) {
                                       // Callback is invoked more than once.
                                       // Prevent multiple iterations over forward-only result set.
                                       if (!isset($result)) {
                                           $result = iterator_to_array($dataSource);
                                       }
                                       return true;
                                   }
                               )
                           )->will($this->returnSelf());

        $hydrator = $this->getMockBuilder('Database\Hydrator\Clients')->disableOriginalConstructor()->getMock();
        $hydrator->method('getExtractorMap')->willReturn($this->_map);
        $hydrator->method('extractName')->willReturnCallback(
            function($name) {
                return $this->_map[$name];
            }
        );

        $clients = $this->getMockBuilder('Database\Table\Clients')->disableOriginalConstructor()->getMock();
        $clients->method('getResultSetPrototype')->willReturn($resultSetPrototype);
        $clients->method('getTable')->willReturn('clients');
        $clients->method('getHydrator')->willReturn($hydrator);

        $model = $this->_getModel(
            array(
                'Database\Table\Clients' => $clients,
                'Model\Client\CustomFieldManager' => $customFieldManager,
            )
        );

        // The mock object has a unique class name which survives the clone
        // operation and can be used to check that the result set prototype was
        // really pulled from the table gateway.
        $this->assertInstanceOf(
            get_class($resultSetPrototype),
            $model->getClients(
                $properties, $order, $direction, $filter, $search, $operator, $invert, $addSearchColumns
            )
        );

        $this->assertEquals($expected, $result);
    }

    public function getClientsGroupFilterProvider()
    {
        return array(
            array('MemberOf', 1, false, array('id' => 1)),
            array('MemberOf', 2, false, array('id' => 2)),
            array('ExcludedFrom', 1, false, array('id' => 2)),
            array('MemberOf', 1, true, array('id' => 1, 'static' => \Model_GroupMembership::TYPE_DYNAMIC)),
            array('MemberOf', 2, true, array('id' => 2, 'static' => \Model_GroupMembership::TYPE_STATIC)),
        );
    }

    /**
     * @dataProvider getClientsGroupFilterProvider
     */
    public function testGetClientsGroupFilter($filter, $groupId, $addColumn, $expectedClient)
    {
        $group = $this->getMock('Model\Group\Group');
        $group->method('offsetGet')->with('Id')->willReturn($groupId);
        $group->expects($this->once())->method('update');

        $resultSetPrototype = $this->getMock('Zend\Db\ResultSet\HydratingResultSet');
        $resultSetPrototype->expects($this->once())
                           ->method('initialize')
                           ->with(
                               $this->callback(
                                   function($dataSource) use(&$result) {
                                       // Callback is invoked more than once.
                                       // Prevent multiple iterations over forward-only result set.
                                       if (!isset($result)) {
                                           $result = iterator_to_array($dataSource);
                                       }
                                       return true;
                                   }
                               )
                           )->will($this->returnSelf());

        $hydrator = $this->getMockBuilder('Database\Hydrator\Clients')->disableOriginalConstructor()->getMock();
        $hydrator->method('getExtractorMap')->willReturn($this->_map);

        $clients = $this->getMockBuilder('Database\Table\Clients')->disableOriginalConstructor()->getMock();
        $clients->method('getResultSetPrototype')->willReturn($resultSetPrototype);
        $clients->method('getHydrator')->willReturn($hydrator);

        $model = $this->_getModel(array('Database\Table\Clients' => $clients));
        $model->getClients(array('Id'), 'Id', 'asc', $filter, $group, null, null, $addColumn);
        $this->assertEquals($expectedClient, $result[0]);
    }

    public function getClientsDistinctProvider()
    {
        return array(
            array(
                false,
                array(
                    array('id' => 1, 'software_name' => 'name1'),
                    array('id' => 2, 'software_name' => 'name1'),
                    array('id' => 2, 'software_name' => 'name1'),
                )
            ),
            array(
                true,
                array(
                    array('id' => 1, 'software_name' => 'name1'),
                    array('id' => 2, 'software_name' => 'name1'),
                )
            ),
        );
    }

    /**
     * @dataProvider getClientsDistinctProvider
     */
    public function testGetClientsDistinct($distinct, $expected)
    {
        $resultSetPrototype = $this->getMock('Zend\Db\ResultSet\HydratingResultSet');
        $resultSetPrototype->expects($this->once())
                           ->method('initialize')
                           ->with(
                               $this->callback(
                                   function($dataSource) use(&$result) {
                                       // Callback is invoked more than once.
                                       // Prevent multiple iterations over forward-only result set.
                                       if (!isset($result)) {
                                           $result = iterator_to_array($dataSource);
                                       }
                                       return true;
                                   }
                               )
                           )->will($this->returnSelf());

        $hydrator = $this->getMockBuilder('Database\Hydrator\Clients')->disableOriginalConstructor()->getMock();
        $hydrator->method('getExtractorMap')->willReturn($this->_map);

        $clients = $this->getMockBuilder('Database\Table\Clients')->disableOriginalConstructor()->getMock();
        $clients->method('getResultSetPrototype')->willReturn($resultSetPrototype);
        $clients->method('getHydrator')->willReturn($hydrator);

        $model = $this->_getModel(array('Database\Table\Clients' => $clients));
        $model->getClients(array('Id'), 'Id', 'asc', 'Software.Name', 'name1', null, null, true, $distinct);
        $this->assertEquals($expected, $result);
    }

    public function getClientsExceptionsProvider()
    {
        return array(
            array(array('Id'), 'invalid', '', 'InvalidArgumentException', 'Invalid filter: invalid'),
            array(array('Id'), 'CustomFields.invalid', '', 'LogicException', 'Unsupported type: invalid'),
            array(array('Id'), 'CpuClock', '=', 'DomainException', 'Invalid comparison operator: ='),
            array(array('Id'), 'LastContactDate', '=', 'DomainException', 'Invalid comparison operator: ='),
            array(
                array('Id'),
                'Id',
                '=',
                'LogicException',
                'invertResult cannot be used on Id filter'
            ),
            array(
                array('Id'),
                'PackageNonnotified',
                '=',
                'LogicException',
                'invertResult cannot be used on PackageNonnotified filter'
            ),
            array(
                array('Id'),
                'PackageNotified',
                '=',
                'LogicException',
                'invertResult cannot be used on PackageNotified filter'
            ),
            array(
                array('Id'),
                'PackageSuccess',
                '=',
                'LogicException',
                'invertResult cannot be used on PackageSuccess filter'
            ),
            array(
                array('Id'),
                'PackageError',
                '=',
                'LogicException',
                'invertResult cannot be used on PackageError filter'
            ),
            array(
                array('Id'),
                'Software',
                '=',
                'LogicException',
                'invertResult cannot be used on Software filter'
            ),
            array(
                array('Id'),
                'MemberOf',
                '=',
                'LogicException',
                'invertResult cannot be used on MemberOf filter'
            ),
            array(
                array('Id'),
                'ExcludedFrom',
                '=',
                'LogicException',
                'invertResult cannot be used on ExcludedFrom filter'
            ),
        );
    }
    /**
     * @dataProvider getClientsExceptionsProvider
     */
    public function testGetClientsExceptions($properties, $filter, $operator, $exceptionType, $message)
    {
        $this->setExpectedException($exceptionType, $message);

        $customFieldManager = $this->getMockBuilder('Model\Client\CustomFieldManager')
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $customFieldManager->method('getFields')->willReturn(
            array(
                'invalid' => 'invalid',
            )
        );

        $model = $this->_getModel(array('Model\Client\CustomFieldManager' => $customFieldManager));
        $model->getClients($properties, 'Id', 'asc', $filter, '2015-08-17', $operator, true);
    }

    public function testGetClientSelect()
    {
        $model = $this->_getModel();
        $result = $model->getClients(null, null, 'asc', null, null, null, null, true, false, false);
        $this->assertInstanceOf('Zend\Db\Sql\Select', $result);
    }

    public function testGetClient()
    {
        $resultSet = $this->getMock('Zend\Db\ResultSet\HydratingResultSet');
        $resultSet->method('current')->willReturn('client');

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('getClients'))->getMock();
        $model->method('getClients')->with(null, null, null, 'Id', 42)->willReturn($resultSet);
        $this->assertEquals('client', $model->getClient(42));
    }

    public function testGetClientInvalidId()
    {
        $this->setExpectedException('RuntimeException', 'Invalid client ID: 42');
        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('getClients'))->getMock();
        $model->method('getClients')->with(null, null, null, 'Id', 42)->willReturn(array());
        $model->getClient(42);
    }
}
