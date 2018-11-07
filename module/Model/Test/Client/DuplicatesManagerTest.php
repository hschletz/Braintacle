<?php
/**
 * Tests for Model\Client\DuplicatesManager
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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
    protected $_allOptions = [
        \Model\Client\DuplicatesManager::MERGE_CUSTOM_FIELDS,
        \Model\Client\DuplicatesManager::MERGE_GROUPS,
        \Model\Client\DuplicatesManager::MERGE_PACKAGES,
        \Model\Client\DuplicatesManager::MERGE_PRODUCT_KEY,
    ];

    /** {@inheritdoc} */
    protected static $_tables = array(
        'ClientConfig',
        'ClientsAndGroups',
        'ClientSystemInfo',
        'Clients',
        'DuplicateAssetTags',
        'DuplicateMacAddresses',
        'DuplicateSerials',
        'NetworkInterfaces',
    );

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
        static::$serviceManager->get('Database\Table\DuplicateMacAddresses')->delete(true);
        static::$serviceManager->get('Database\Table\DuplicateSerials')->delete(true);
        static::$serviceManager->get('Database\Table\DuplicateAssetTags')->delete(true);
        $this->assertEquals(2, $duplicates->count('MacAddress'));
        $this->assertEquals(2, $duplicates->count('Serial'));
        $this->assertEquals(2, $duplicates->count('AssetTag'));

        // Test invalid criteria
        $this->expectException('InvalidArgumentException');
        $duplicates->count('invalid');
    }

    public function findProvider()
    {
        $client2 = array(
            'id' => '2',
            'name' => 'Name2',
            'lastcome' => '2013-12-23 13:02:33',
            'ssn' => 'duplicate',
            'assettag' => 'duplicate',
            'networkinterface_macaddr' => '00:00:5E:00:53:01',
        );
        $client3 = array (
            'id' => '3',
            'name' => 'Name2',
            'lastcome' => '2013-12-23 13:03:33',
            'ssn' => 'duplicate',
            'assettag' => 'duplicate',
            'networkinterface_macaddr' => '00:00:5E:00:53:01',
        );
        $defaultOrder = array('clients.id' => 'asc', 'name');
        return array(
            array('MacAddress', 'Id', 'asc', false, $defaultOrder, array()),
            array('Serial', 'Id', 'asc', false, $defaultOrder, array()),
            array('AssetTag', 'Id', 'asc', false, $defaultOrder, array()),
            array('MacAddress', 'Id', 'asc', true, $defaultOrder, array($client2, $client3)),
            array('Serial', 'Id', 'asc', true, $defaultOrder, array($client2, $client3)),
            array('AssetTag', 'Id', 'asc', true, $defaultOrder, array($client2, $client3)),
            array('Name', 'Id', 'asc', false, $defaultOrder, array($client2, $client3)),
            array('Name', 'Id', 'desc', false, array('clients.id' => 'desc', 'name'), array($client3, $client2)),
            array(
                'Name',
                'Name',
                'asc',
                false,
                array('clients.name' => 'asc', 'clients.id'),
                array($client2, $client3)
            ),
            array(
                'Name',
                'NetworkInterface.MacAddress',
                'asc',
                false,
                array('networkinterface_macaddr' => 'asc', 'name', 'clients.id'),
                array($client2, $client3)
            ),
        );
    }

    /**
     * @dataProvider findProvider
     */
    public function testFind($criteria, $order, $direction, $clearBlacklist, $expectedOrder, $expectedResult)
    {
        if ($clearBlacklist) {
            static::$serviceManager->get('Database\Table\DuplicateMacAddresses')->delete(true);
            static::$serviceManager->get('Database\Table\DuplicateSerials')->delete(true);
            static::$serviceManager->get('Database\Table\DuplicateAssetTags')->delete(true);
        }

        $ordercolumns = array(
            'Id' => 'clients.id',
            'Name' => 'clients.name',
            'NetworkInterface.MacAddress' => 'networkinterface_macaddr',
        );

        $sql = new \Zend\Db\Sql\Sql(static::$serviceManager->get('Db'), 'clients');

        $select = $sql->select()
                      ->columns(array('id', 'name', 'lastcome', 'ssn', 'assettag'))
                      ->order(array($ordercolumns[$order] => $direction));

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClients')
                      ->with(
                          array('Id', 'Name', 'LastContactDate', 'Serial', 'AssetTag'),
                          $order,
                          $direction,
                          null,
                          null,
                          null,
                          null,
                          false,
                          false,
                          false
                      )
                      ->willReturn($select);

        $clients = $this->getMockBuilder('Database\Table\Clients')
                        ->disableOriginalConstructor()
                        ->setMethods(array('getSql', 'selectWith'))
                        ->getMock();
        $clients->method('getSql')->willReturn($sql);
        $clients->method('selectWith')
                ->with(
                    $this->callback(
                        function ($select) use ($expectedOrder) {
                            return $select->getRawState($select::ORDER) == $expectedOrder;
                        }
                    )
                )
                ->willReturnCallback(
                    function ($select) use ($sql) {
                        // Build simple result set to bypass hydrator
                        $resultSet = new \Zend\Db\ResultSet\ResultSet;
                        $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());
                        return $resultSet;
                    }
                );

        $duplicates = $this->_getModel(
            array(
                'Database\Table\Clients' => $clients,
                'Model\Client\ClientManager' => $clientManager,
            )
        );

        $resultSet = $duplicates->find($criteria, $order, $direction);
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $resultSet);
        $this->assertEquals($expectedResult, $resultSet->toArray());
    }

    public function testFindInvalidCriteria()
    {
        // Test invalid criteria
        $this->expectException('InvalidArgumentException');
        $this->_getModel()->find('invalid');
    }

    public function mergeNoneWithLessThan2ClientsProvider()
    {
        return [
            [[]],
            [[1]],
            [[1, 1]], // IDs get deduplicated
        ];
    }

    /** @dataProvider mergeNoneWithLessThan2ClientsProvider */
    public function testMergeWithLessThan2Clients($clientIds)
    {
        $model = $this->getMockBuilder($this->_getClass())
                      ->disableOriginalConstructor()
                      ->setMethodsExcept(['merge'])
                      ->getMock();

        $model->expects($this->never())->method('mergeCustomFields');
        $model->expects($this->never())->method('mergeGroups');
        $model->expects($this->never())->method('mergePackages');

        $model->merge($clientIds, $this->_allOptions);
    }

    public function testMergeLockingError()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Cannot lock client 2');

        $connection = $this->createMock('Zend\Db\Adapter\Driver\ConnectionInterface');
        $connection->expects($this->at(0))->method('beginTransaction');
        $connection->expects($this->at(1))->method('rollback');
        $connection->expects($this->never())->method('commit');

        $clients = $this->createMock('Database\Table\Clients');
        $clients->method('getConnection')->willReturn($connection);

        $client = $this->createMock('Model\Client\Client');
        $client->method('lock')->willReturn(false);

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')->willReturn($client);
        $clientManager->expects($this->never())->method('deleteClient');

        $model = $this->getMockBuilder($this->_getClass())
                      ->disableOriginalConstructor()
                      ->setMethodsExcept(['merge'])
                      ->getMock();

        $model->expects($this->never())->method('mergeCustomFields');
        $model->expects($this->never())->method('mergeGroups');
        $model->expects($this->never())->method('mergePackages');

        $proxy = new \SebastianBergmann\PeekAndPoke\Proxy($model);
        $proxy->_clients = $clients;
        $proxy->_clientManager = $clientManager;
        $proxy->merge([2, 3], $this->_allOptions);
    }

    public function mergeWithoutMergingAttributesProvider()
    {
        return [
            [[1, 2, 3]],
            [[1, 1, 2, 2, 3, 3]], // Test deduplication
            [[3, 2, 1]], // Test reversed order - should not make a difference
        ];
    }

    /** @dataProvider mergeWithoutMergingAttributesProvider */
    public function testMergeWithoutMergingAttributes($clientIds)
    {
        $dateTime1 = $this->createMock('DateTime');
        $dateTime1->method('getTimestamp')->willReturn(111);
        $dateTime2 = $this->createMock('DateTime');
        $dateTime2->method('getTimestamp')->willReturn(222);
        $dateTime3 = $this->createMock('DateTime');
        $dateTime3->method('getTimestamp')->willReturn(333);

        $client1 = $this->createMock('Model\Client\Client');
        $client1->expects($this->at(0))->method('lock')->willReturn(true);
        $client1->expects($this->at(1))->method('offsetGet')->with('LastContactDate')->willReturn($dateTime1);
        $client1->expects($this->never())->method('unlock');

        $client2 = $this->createMock('Model\Client\Client');
        $client2->expects($this->at(0))->method('lock')->willReturn(true);
        $client2->expects($this->at(1))->method('offsetGet')->with('LastContactDate')->willReturn($dateTime2);
        $client2->expects($this->never())->method('unlock');

        // The newest client that gets preserved
        $client3 = $this->createMock('Model\Client\Client');
        $client3->expects($this->at(0))->method('lock')->willReturn(true);
        $client3->expects($this->at(1))->method('offsetGet')->with('LastContactDate')->willReturn($dateTime3);
        $client3->expects($this->at(2))->method('unlock');

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')
                      ->willReturnMap([[1, $client1], [2, $client2], [3, $client3]]);
        $clientManager->expects($this->exactly(2))
                      ->method('deleteClient')
                      ->withConsecutive([$this->identicalTo($client1)], [$this->identicalTo($client2)]);

        $connection = $this->createMock('Zend\Db\Adapter\Driver\ConnectionInterface');
        $connection->expects($this->at(0))->method('beginTransaction');
        $connection->expects($this->at(1))->method('commit');
        $connection->expects($this->never())->method('rollback');

        $clients = $this->createMock('Database\Table\Clients');
        $clients->method('getConnection')->willReturn($connection);

        $model = $this->getMockBuilder($this->_getClass())
                      ->disableOriginalConstructor()
                      ->setMethodsExcept(['merge'])
                      ->getMock();

        $model->expects($this->never())->method('mergeCustomFields');
        $model->expects($this->never())->method('mergeGroups');
        $model->expects($this->never())->method('mergePackages');

        $proxy = new \SebastianBergmann\PeekAndPoke\Proxy($model);
        $proxy->_clients = $clients;
        $proxy->_clientManager = $clientManager;
        $proxy->merge($clientIds, []);
    }

    public function mergeWithMergingAttributesProvider()
    {
        return [
            [[\Model\Client\DuplicatesManager::MERGE_CUSTOM_FIELDS]],
            [[\Model\Client\DuplicatesManager::MERGE_GROUPS]],
            [[\Model\Client\DuplicatesManager::MERGE_PACKAGES]],
            [[\Model\Client\DuplicatesManager::MERGE_PRODUCT_KEY]],
            [$this->_allOptions]
        ];
    }

    /** @dataProvider mergeWithMergingAttributesProvider */
    public function testMergeWithMergingAttributes($options)
    {
        $dateTime1 = $this->createMock('DateTime');
        $dateTime1->method('getTimestamp')->willReturn(111);
        $dateTime2 = $this->createMock('DateTime');
        $dateTime2->method('getTimestamp')->willReturn(222);
        $dateTime3 = $this->createMock('DateTime');
        $dateTime3->method('getTimestamp')->willReturn(333);

        $client1 = $this->createMock('Model\Client\Client');
        $client1->method('lock')->willReturn(true);
        $client1->method('offsetGet')->with('LastContactDate')->willReturn($dateTime1);

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('lock')->willReturn(true);
        $client2->method('offsetGet')->with('LastContactDate')->willReturn($dateTime2);

        // The newest client that gets preserved
        $client3 = $this->createMock('Model\Client\Client');
        $client3->method('lock')->willReturn(true);
        $client3->method('offsetGet')->with('LastContactDate')->willReturn($dateTime3);

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')
                      ->willReturnMap([[1, $client1], [2, $client2], [3, $client3]]);

        $connection = $this->createMock('Zend\Db\Adapter\Driver\ConnectionInterface');

        $clients = $this->createMock('Database\Table\Clients');
        $clients->method('getConnection')->willReturn($connection);

        $model = $this->getMockBuilder($this->_getClass())
                      ->disableOriginalConstructor()
                      ->setMethodsExcept(['merge'])
                      ->getMock();

        foreach ($this->_allOptions as $option) {
            if (in_array($option, $options)) {
                $model->expects($this->once())
                      ->method($option)
                      ->with($this->identicalTo($client3), $this->identicalTo([$client1, $client2]));
            } else {
                $model->expects($this->never())->method($option);
            }
        }

        $proxy = new \SebastianBergmann\PeekAndPoke\Proxy($model);
        $proxy->_clients = $clients;
        $proxy->_clientManager = $clientManager;
        $proxy->merge([1, 2, 3], $options);
    }

    public function testMergeCustomFields()
    {
        $newestClient = $this->createMock('Model\Client\Client');
        $newestClient->expects($this->once())->method('setCustomFields')->with(['field' => 'value']);

        $olderClients = [
            ['CustomFields' => ['field' => 'value']],
            ['CustomFields' => ['field' => 'ignored']],
        ];

        $model = $this->_getModel();
        $model->mergeCustomFields($newestClient, $olderClients);
    }

    public function testMergeGroups()
    {
        $memberships = [
            1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
            2 => \Model\Client\client::MEMBERSHIP_NEVER,
            3 => \Model\Client\client::MEMBERSHIP_ALWAYS,
        ];

        $newestClient = $this->createMock('Model\Client\Client');
        $newestClient->expects($this->once())->method('setGroupMemberships')->with($memberships);

        $client1 = $this->createMock('Model\Client\Client');
        $client1->method('getGroupMemberships')->with(\Model\Client\Client::MEMBERSHIP_MANUAL)->willReturn([
            1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
            2 => \Model\Client\client::MEMBERSHIP_NEVER,
        ]);

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('getGroupMemberships')->with(\Model\Client\Client::MEMBERSHIP_MANUAL)->willReturn([
            2 => \Model\Client\client::MEMBERSHIP_NEVER,
            3 => \Model\Client\client::MEMBERSHIP_ALWAYS,
        ]);

        $olderClients = [$client1, $client2];

        $model = $this->_getModel();
        $model->mergeGroups($newestClient, $olderClients);
    }

    public function testMergeGroupsWithConflictingMemberships()
    {
        // The resulting membership type is undefinded. Just check for the
        // correct group ID and size.
        $newestClient = $this->createMock('Model\Client\Client');
        $newestClient->expects($this->once())
                     ->method('setGroupMemberships')
                     ->with($this->logicalAnd($this->countOf(1), $this->arrayHasKey(1)));

        $client1 = $this->createMock('Model\Client\Client');
        $client1->method('getGroupMemberships')->with(\Model\Client\Client::MEMBERSHIP_MANUAL)->willReturn([
            1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
        ]);

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('getGroupMemberships')->with(\Model\Client\Client::MEMBERSHIP_MANUAL)->willReturn([
            1 => \Model\Client\client::MEMBERSHIP_NEVER,
        ]);

        $olderClients = [$client1, $client2];

        $model = $this->_getModel();
        $model->mergeGroups($newestClient, $olderClients);
    }

    public function testMergePackages()
    {
        $newestClient = ['Id' => 3];
        $olderClients = [['Id' => 2]];

        $model = $this->_getModel();
        $model->mergePackages($newestClient, $olderClients);

        $this->assertTablesEqual(
            $this->_loadDataSet('MergePackages')->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                <<<EOT
                    SELECT hardware_id, name, ivalue, tvalue
                    FROM devices
                    WHERE hardware_id = 3
                    ORDER BY hardware_id, name, ivalue
EOT
            )
        );
    }

    public function testMergePackagesNoPackagesToMerge()
    {
        $newestClient = ['Id' => 4];
        $olderClients = [['Id' => 1]];

        $model = $this->_getModel();
        $model->mergePackages($newestClient, $olderClients);

        // Table should be unchanged
        $this->assertTablesEqual(
            $this->_loadDataSet()->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                <<<EOT
                    SELECT hardware_id, name, ivalue, tvalue
                    FROM devices
                    ORDER BY hardware_id, name, ivalue
EOT
            )
        );
    }

    public function mergeProductKeyProvider()
    {
        $clientNoWindows = ['Windows' => null];
        $clientWindowsManualKey1 = ['Windows' => ['ManualProductKey' => 'key1']];
        $clientWindowsManualKey2 = ['Windows' => ['ManualProductKey' => 'key2']];
        $clientWindowsNoKey1 = ['Windows' => ['ManualProductKey' => null]];
        $clientWindowsNoKey2 = ['Windows' => ['ManualProductKey' => null]];
        // no merge because...
        return [
            [$clientNoWindows, [$clientWindowsManualKey1]], // ... newest client has no Windows property
            [$clientWindowsManualKey1, [$clientWindowsManualKey2]], // ... newest client already has manual key
            [$clientWindowsNoKey1, [$clientNoWindows]], // ... newest has no manual key and older has no Windows
            [$clientWindowsNoKey1, [$clientWindowsNoKey2]], // ... both have Windows property but no manual key
        ];
    }

    /** @dataProvider mergeProductKeyProvider */
    public function testMergeProductKeyNoMerge($newestClient, $olderClients)
    {
        $softwareManager = $this->createMock('Model\SoftwareManager');
        $softwareManager->expects($this->never())->method('setProductKey');

        $model = $this->getMockBuilder($this->_getClass())
                      ->disableOriginalConstructor()
                      ->setMethods(null)
                      ->getMock();

        $proxy = new \SebastianBergmann\PeekAndPoke\Proxy($model);
        $proxy->_softwareManager = $softwareManager;

        $proxy->mergeProductKey($newestClient, $olderClients);
    }

    public function testMergeProductKeyMerge()
    {
        $newestClient = $this->createMock('Model\Client\Client');
        $newestClient->expects($this->atLeastOnce())
                     ->method('offsetGet')
                     ->with('Windows')
                     ->willReturn(['ManualProductKey' => null]);

        $olderClients = [
            ['Windows' => ['ManualProductKey' => 'key1']], // never evaluated
            ['Windows' => ['ManualProductKey' => 'key2']], // first client with key, picked
            ['Windows' => ['ManualProductKey' => null]], // no key, skipped
        ];

        $softwareManager = $this->createMock('Model\SoftwareManager');
        $softwareManager->expects($this->once())->method('setProductKey')->with($newestClient, 'key2');

        $model = $this->getMockBuilder($this->_getClass())
                      ->disableOriginalConstructor()
                      ->setMethods(null)
                      ->getMock();

        $proxy = new \SebastianBergmann\PeekAndPoke\Proxy($model);
        $proxy->_softwareManager = $softwareManager;

        $proxy->mergeProductKey($newestClient, $olderClients);
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

        $this->expectException('InvalidArgumentException');
        $duplicates->allow('invalid', 'test');
    }
}
