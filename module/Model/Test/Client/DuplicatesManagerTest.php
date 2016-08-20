<?php
/**
 * Tests for Model\Client\DuplicatesManager
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
        $this->setExpectedException('InvalidArgumentException');
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
        $this->setExpectedException('InvalidArgumentException');
        $this->_getModel()->find('invalid');
    }

    /**
     * Test merge() with less than 2 clients (no action is taken)
     */
    public function testMergeNone()
    {
        $mergeIds = array(2, 2); // Test deduplication of IDs

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->expects($this->never())->method('getClient');

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->never())->method('update');

        $model = $this->_getModel(
            array(
                'Model\Client\ClientManager' => $clientManager,
                'Database\Table\ClientConfig' => $clientConfig,
            )
        );
        $model->merge($mergeIds, true, true, true);
    }

    /**
     * Test merge() with locking error (operation should abort)
     */
    public function testMergeLockingError()
    {
        $mergeIds = array(2, 3);

        $client = $this->createMock('Model\Client\Client');
        $client->method('lock')->willReturn(false);
        $client->expects($this->never())->method('setCustomFields');
        $client->expects($this->never())->method('setGroupMemberships');

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')->willReturn($client);
        $clientManager->expects($this->never())->method('deleteClient');

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->never())->method('update');

        $this->setExpectedException('RuntimeException', 'Cannot lock client 2');
        $model = $this->_getModel(
            array(
                'Model\Client\ClientManager' => $clientManager,
                'Database\Table\ClientConfig' => $clientConfig,
            )
        );
        $model->merge($mergeIds, true, true, true);
    }

    /**
     * Test merge() with no extra merging (just delete duplicate)
     */
    public function testMergeBasic()
    {
        $mergeIds = array(2, 2, 3, 3); // Test deduplication of IDs

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:02:33'));
        $client2->method('lock')->willReturn(true);
        $client2->expects($this->never())->method('setCustomFields');
        $client2->expects($this->never())->method('setGroupMemberships');

        $client3 = $this->createMock('Model\Client\Client');
        $client3->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:03:33'));
        $client3->method('lock')->willReturn(true);
        $client3->expects($this->once())->method('unlock');
        $client3->expects($this->never())->method('setCustomFields');
        $client3->expects($this->never())->method('setGroupMemberships');

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')
                      ->withConsecutive(array(2), array(3))
                      ->will($this->onConsecutiveCalls($client2, $client3));
        $clientManager->expects($this->once())->method('deleteClient')->with($this->identicalTo($client2), false);

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->never())->method('update');

        $model = $this->_getModel(
            array(
                'Model\Client\ClientManager' => $clientManager,
                'Database\Table\ClientConfig' => $clientConfig,
            )
        );
        $model->merge($mergeIds, false, false, false); // Don't merge any extra information
    }

    /**
     * Test merge() with reverse specification of IDs (ensure independence from ID ordering)
     */
    public function testMergeReverse()
    {
        $mergeIds = array(3, 3, 2, 2); // Test deduplication of IDs

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:02:33'));
        $client2->method('lock')->willReturn(true);
        $client2->expects($this->never())->method('setCustomFields');
        $client2->expects($this->never())->method('setGroupMemberships');

        $client3 = $this->createMock('Model\Client\Client');
        $client3->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:03:33'));
        $client3->method('lock')->willReturn(true);
        $client3->expects($this->once())->method('unlock');
        $client3->expects($this->never())->method('setCustomFields');
        $client3->expects($this->never())->method('setGroupMemberships');

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')
                      ->withConsecutive(array(3), array(2))
                      ->will($this->onConsecutiveCalls($client3, $client2));
        $clientManager->expects($this->once())->method('deleteClient')->with($this->identicalTo($client2), false);

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->never())->method('update');

        $model = $this->_getModel(
            array(
                'Model\Client\ClientManager' => $clientManager,
                'Database\Table\ClientConfig' => $clientConfig,
            )
        );
        $model->merge($mergeIds, false, false, false);
    }

    /**
     * Test merge() with merging of custom fields
     */
    public function testMergeCustomFields()
    {
        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('offsetGet')
                ->withConsecutive(array('LastContactDate'), array('CustomFields'))
                ->will($this->onConsecutiveCalls(new \DateTime('2013-12-23 13:02:33'), 'custom_fields'));
        $client2->method('lock')->willReturn(true);
        $client2->expects($this->never())->method('setCustomFields');
        $client2->expects($this->never())->method('setGroupMemberships');

        $client3 = $this->createMock('Model\Client\Client');
        $client3->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:03:33'));
        $client3->method('lock')->willReturn(true);
        $client3->expects($this->once())->method('unlock');
        $client3->expects($this->once())->method('setCustomFields')->with('custom_fields');
        $client3->expects($this->never())->method('setGroupMemberships');

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')
                      ->withConsecutive(array(2), array(3))
                      ->will($this->onConsecutiveCalls($client2, $client3));
        $clientManager->expects($this->once())->method('deleteClient')->with($this->identicalTo($client2), false);

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->never())->method('update');

        $model = $this->_getModel(
            array(
                'Model\Client\ClientManager' => $clientManager,
                'Database\Table\ClientConfig' => $clientConfig,
            )
        );
        $model->merge(array(2, 3), true, false, false);
    }

    /**
     * Test merge() with merging of group memberships
     */
    public function testMergeGroups()
    {
        $client1 = $this->createMock('Model\Client\Client');
        $client1->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:01:33'));
        $client1->method('lock')->willReturn(true);
        $client1->method('getGroupMemberships')->with(\Model\Client\Client::MEMBERSHIP_MANUAL)->willReturn(
            array(
                1 => 'membership1',
                2 => 'membership2',
            )
        );
        $client1->expects($this->never())->method('setCustomFields');
        $client1->expects($this->never())->method('setGroupMemberships');

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:02:33'));
        $client2->method('lock')->willReturn(true);
        $client2->method('getGroupMemberships')->with(\Model\Client\Client::MEMBERSHIP_MANUAL)->willReturn(
            array(
                2 => 'membership2',
                3 => 'membership3',
            )
        );
        $client2->expects($this->never())->method('setCustomFields');
        $client2->expects($this->never())->method('setGroupMemberships');

        $client3 = $this->createMock('Model\Client\Client');
        $client3->method('offsetGet')->with('LastContactDate')->willReturn(new \DateTime('2013-12-23 13:03:33'));
        $client3->method('lock')->willReturn(true);
        $client3->expects($this->once())->method('unlock');
        $client3->expects($this->never())->method('setCustomFields');
        $client3->expects($this->once())->method('setGroupMemberships')->with(
            array(
                1 => 'membership1',
                2 => 'membership2',
                3 => 'membership3',
            )
        );

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')
                      ->withConsecutive(array(1), array(2), array(3))
                      ->will($this->onConsecutiveCalls($client1, $client2, $client3));
        $clientManager->expects($this->exactly(2))
                      ->method('deleteClient')
                      ->withConsecutive(
                          array($this->identicalTo($client1), false),
                          array($this->identicalTo($client2), false)
                      );

        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->expects($this->never())->method('update');

        $model = $this->_getModel(
            array(
                'Model\Client\ClientManager' => $clientManager,
                'Database\Table\ClientConfig' => $clientConfig,
            )
        );
        $model->merge(array(1, 2, 3), false, true, false);
    }

    /**
     * Test merge() with merging of package assignments
     */
    public function testMergePackages()
    {
        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('offsetGet')
                ->withConsecutive(array('LastContactDate'), array('Id'))
                ->will($this->onConsecutiveCalls(new \DateTime('2013-12-23 13:02:33'), 2));
        $client2->method('lock')->willReturn(true);
        $client2->expects($this->never())->method('setCustomFields');
        $client2->expects($this->never())->method('setGroupMemberships');

        $client3 = $this->createMock('Model\Client\Client');
        $client3->method('offsetGet')
                ->withConsecutive(array('LastContactDate'), array('Id'))
                ->will($this->onConsecutiveCalls(new \DateTime('2013-12-23 13:03:33'), 3));
        $client3->method('lock')->willReturn(true);
        $client3->expects($this->once())->method('unlock');
        $client3->expects($this->never())->method('setCustomFields');
        $client3->expects($this->never())->method('setGroupMemberships');

        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClient')
                      ->withConsecutive(array(2), array(3))
                      ->will($this->onConsecutiveCalls($client2, $client3));
        $clientManager->expects($this->once())->method('deleteClient')->with($this->identicalTo($client2), false);

        $model = $this->_getModel(
            array(
                'Model\Client\ClientManager' => $clientManager,
            )
        );
        $model->merge(array(2, 3), false, false, true);
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
