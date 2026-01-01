<?php

/**
 * Tests for Model\Client\DuplicatesManager
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Direction;
use Braintacle\Duplicates\Criterion;
use Braintacle\Duplicates\DuplicatesColumn;
use Database\Table\Clients;
use Database\Table\DuplicateAssetTags;
use Database\Table\DuplicateMacAddresses;
use Database\Table\DuplicateSerials;
use Database\Table\NetworkInterfaces;
use Laminas\Db\Adapter\Adapter;
use Mockery;
use Model\Client\ClientManager;
use Model\Client\DuplicatesManager;
use Model\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use UnhandledMatchError;

/**
 * Tests for Model\Client\DuplicatesManager
 */
class DuplicatesManagerTest extends AbstractTestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected static $_tables = array(
        'DuplicateAssetTags',
        'DuplicateMacAddresses',
        'DuplicateSerials',
    );

    /**
     * Tests for count()
     */
    public function testCount()
    {
        $duplicates = $this->getModel();

        // These criteria are initially allowed duplicate.
        $this->assertEquals(0, $duplicates->count(Criterion::MacAddress));
        $this->assertEquals(0, $duplicates->count(Criterion::Serial));
        $this->assertEquals(0, $duplicates->count(Criterion::AssetTag));

        // Duplicate names are always counted.
        $this->assertEquals(2, $duplicates->count(Criterion::Name));

        // Clear list of allowed duplicate values and re-check.
        static::$serviceManager->get('Database\Table\DuplicateMacAddresses')->delete(true);
        static::$serviceManager->get('Database\Table\DuplicateSerials')->delete(true);
        static::$serviceManager->get('Database\Table\DuplicateAssetTags')->delete(true);

        $this->assertEquals(2, $duplicates->count(Criterion::MacAddress));
        $this->assertEquals(2, $duplicates->count(Criterion::Serial));
        $this->assertEquals(2, $duplicates->count(Criterion::AssetTag));
    }

    public static function findProvider()
    {
        $client2 = array(
            'id' => '2',
            'name' => 'Name2',
            'lastcome' => '2013-12-23 13:02:33',
            'ssn' => 'duplicate',
            'assettag' => 'duplicate',
            'networkinterface_macaddr' => '00:00:5E:00:53:01',
        );
        $client3 = array(
            'id' => '3',
            'name' => 'Name2',
            'lastcome' => '2013-12-23 13:03:33',
            'ssn' => 'duplicate',
            'assettag' => 'duplicate',
            'networkinterface_macaddr' => '00:00:5E:00:53:01',
        );
        $defaultOrder = array('clients.id' => 'asc', 'name');
        return [
            [Criterion::MacAddress, DuplicatesColumn::Id, Direction::Ascending, false, $defaultOrder, []],
            [Criterion::Serial, DuplicatesColumn::Id, Direction::Ascending, false, $defaultOrder, []],
            [Criterion::AssetTag, DuplicatesColumn::Id, Direction::Ascending, false, $defaultOrder, []],
            [
                Criterion::MacAddress,
                DuplicatesColumn::Id,
                Direction::Ascending,
                true,
                $defaultOrder,
                [$client2, $client3],
            ],
            [Criterion::Serial, DuplicatesColumn::Id, Direction::Ascending, true, $defaultOrder, [$client2, $client3]],
            [
                Criterion::AssetTag,
                DuplicatesColumn::Id,
                Direction::Ascending,
                true,
                $defaultOrder,
                [$client2, $client3],
            ],
            [Criterion::Name, DuplicatesColumn::Id, Direction::Ascending, false, $defaultOrder, [$client2, $client3]],
            [
                Criterion::Name,
                DuplicatesColumn::Id,
                Direction::Descending,
                false,
                ['clients.id' => 'desc', 'name'],
                [$client3, $client2],
            ],
            [
                Criterion::Name,
                DuplicatesColumn::Name,
                Direction::Ascending,
                false,
                ['clients.name' => 'asc', 'clients.id'],
                [$client2, $client3]
            ],
            [
                Criterion::Name,
                DuplicatesColumn::MacAddress,
                Direction::Ascending,
                false,
                ['networkinterface_macaddr' => 'asc', 'name', 'clients.id'],
                [$client2, $client3]
            ],
        ];
    }

    #[DataProvider('findProvider')]
    public function testFind(
        Criterion $criterion,
        DuplicatesColumn $order,
        Direction $direction,
        bool $clearBlacklist,
        array $expectedOrder,
        array $expectedResult
    ) {
        if ($clearBlacklist) {
            static::$serviceManager->get('Database\Table\DuplicateMacAddresses')->delete(true);
            static::$serviceManager->get('Database\Table\DuplicateSerials')->delete(true);
            static::$serviceManager->get('Database\Table\DuplicateAssetTags')->delete(true);
        }

        $ordercolumns = array(
            'Id' => 'clients.id',
            'Name' => 'clients.name',
            'MacAddress' => 'networkinterface_macaddr',
        );

        $sql = new \Laminas\Db\Sql\Sql(static::$serviceManager->get(Adapter::class), 'clients');

        $select = $sql->select()
            ->columns(array('id', 'name', 'lastcome', 'ssn', 'assettag'))
            ->order([$ordercolumns[$order->name] => $direction->value]);

        /** @var MockObject|ClientManager */
        $clientManager = $this->createMock('Model\Client\ClientManager');
        $clientManager->method('getClients')
            ->with(
                array('Id', 'Name', 'LastContactDate', 'Serial', 'AssetTag'),
                $order == DuplicatesColumn::MacAddress ? 'NetworkInterface.MacAddress' : $order->name,
                $direction->value,
                null,
                null,
                null,
                null,
                false,
                false,
                false
            )
            ->willReturn($select);

        $clients = $this->createMock(Clients::class);
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
                    $resultSet = new \Laminas\Db\ResultSet\ResultSet();
                    $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());
                    return $resultSet;
                }
            );

        $duplicates = new DuplicatesManager(
            $clients,
            static::$serviceManager->get(NetworkInterfaces::class),
            static::$serviceManager->get(DuplicateAssetTags::class),
            static::$serviceManager->get(DuplicateSerials::class),
            static::$serviceManager->get(DuplicateMacAddresses::class),
            $clientManager,
        );

        $resultSet = $duplicates->find($criterion, $order, $direction);
        $this->assertInstanceOf('Laminas\Db\ResultSet\AbstractResultSet', $resultSet);
        $this->assertEquals($expectedResult, $resultSet->toArray());
    }

    /**
     * Tests for allow()
     */
    public function testAllow()
    {
        $dataSet = $this->loadDataSet('Allow');
        $connection = $this->getConnection();
        $duplicates = $this->getModel();

        // New entry
        $duplicates->allow(Criterion::MacAddress, '00:00:5E:00:53:00');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_macaddresses'),
            $connection->createQueryTable(
                'blacklist_macaddresses',
                'SELECT macaddress FROM blacklist_macaddresses ORDER BY macaddress'
            )
        );
        // Existing entry should produce no error and no duplicate
        $duplicates->allow(Criterion::MacAddress, '00:00:5E:00:53:01');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_macaddresses'),
            $connection->createQueryTable(
                'blacklist_macaddresses',
                'SELECT macaddress FROM blacklist_macaddresses ORDER BY macaddress'
            )
        );

        // New entry
        $duplicates->allow(Criterion::Serial, 'test');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_serials'),
            $connection->createQueryTable(
                'blacklist_serials',
                'SELECT serial FROM blacklist_serials ORDER BY serial'
            )
        );
        // Existing entry should produce no error and no duplicate
        $duplicates->allow(Criterion::Serial, 'duplicate');
        $this->assertTablesEqual(
            $dataSet->getTable('blacklist_serials'),
            $connection->createQueryTable(
                'blacklist_serials',
                'SELECT serial FROM blacklist_serials ORDER BY serial'
            )
        );

        // New entry
        $duplicates->allow(Criterion::AssetTag, 'test');
        $this->assertTablesEqual(
            $dataSet->getTable('braintacle_blacklist_assettags'),
            $connection->createQueryTable(
                'braintacle_blacklist_assettags',
                'SELECT assettag FROM braintacle_blacklist_assettags ORDER BY assettag'
            )
        );
        // Existing entry should produce no error and no duplicate
        $duplicates->allow(Criterion::AssetTag, 'duplicate');
        $this->assertTablesEqual(
            $dataSet->getTable('braintacle_blacklist_assettags'),
            $connection->createQueryTable(
                'braintacle_blacklist_assettags',
                'SELECT assettag FROM braintacle_blacklist_assettags ORDER BY assettag'
            )
        );

        $this->expectException(UnhandledMatchError::class);
        $duplicates->allow(Criterion::Name, 'test');
    }
}
