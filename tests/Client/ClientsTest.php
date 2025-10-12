<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\Clients;
use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Database\Table;
use Braintacle\Group\Group;
use Braintacle\Group\Groups;
use Braintacle\Group\Membership;
use Braintacle\Locks;
use Braintacle\Test\DatabaseConnection;
use Doctrine\DBAL\Connection;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\Client;
use Model\Client\ItemManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Clients::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
final class ClientsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function createClients(
        ?Connection $connection = null,
        ?ItemManager $itemManager = null,
        ?Groups $groups = null,
        ?Locks $locks = null,
    ): Clients {
        return new Clients(
            $connection ?? $this->createStub(Connection::class),
            $itemManager ?? $this->createStub(ItemManager::class),
            $groups ?? $this->createStub(Groups::class),
            $locks ?? $this->createStub(Locks::class),
        );
    }

    private function createClientsMock(
        array $methods,
        ?Connection $connection = null,
        ?ItemManager $itemManager = null,
        ?Groups $groups = null,
        ?Locks $locks = null,
    ): MockObject | Clients {
        return $this->getMockBuilder(Clients::class)->onlyMethods($methods)->setConstructorArgs([
            $connection ?? $this->createStub(Connection::class),
            $itemManager ?? $this->createStub(ItemManager::class),
            $groups ?? $this->createStub(Groups::class),
            $locks ?? $this->createStub(Locks::class),
        ])->getMock();
    }

    private function fetchColumn(Connection $connection, string $table, string $column)
    {
        return $connection->createQueryBuilder()->select($column)->from($table)->orderBy($column)->fetchFirstColumn();
    }

    private function setupDelete()
    {
        DatabaseConnection::initializeTable(Table::ClientTable, ['id', 'deviceid', 'name'], [
            [1, 'id1', 'name1'],
            [2, 'id2', 'name2'],
        ]);
        DatabaseConnection::initializeTable(Table::AndroidEnvironments, ['hardware_id'], [
            [1],
            [2],
        ]);
        DatabaseConnection::initializeTable(Table::ClientSystemInfo, ['hardware_id'], [
            [1],
            [2],
        ]);
        DatabaseConnection::initializeTable(Table::CustomFields, ['hardware_id'], [
            [1],
            [2],
        ]);
        DatabaseConnection::initializeTable(Table::GroupMemberships, ['hardware_id', 'group_id'], [
            [1, 10],
            [2, 10],
        ]);
        DatabaseConnection::initializeTable(Table::NetworkDevicesIdentified, ['macaddr'], [
            ['02:00:00:00:01:01'],
            ['02:00:00:00:02:01'],
            ['02:00:00:00:02:02'],
        ]);
        DatabaseConnection::initializeTable(Table::NetworkDevicesScanned, ['mac', 'ip', 'netid', 'mask', 'date'], [
            ['02:00:00:00:01:01',  '192.0.2.1', '192.0.2.0', '255.255.255.0', '2025-09-13T15:24:00'],
            ['02:00:00:00:02:01',  '192.0.2.2', '192.0.2.0', '255.255.255.0', '2025-09-13T15:24:00'],
            ['02:00:00:00:02:02',  '192.0.2.4', '192.0.2.0', '255.255.255.0', '2025-09-13T15:24:00'],
        ]);
        DatabaseConnection::initializeTable(Table::NetworkInterfaces, ['hardware_id', 'macaddr', 'description'], [
            [1, '02:00:00:00:01:01', 'description'],
            [2, '02:00:00:00:02:01', 'description'],
            [2, '02:00:00:00:02:02', 'description'],
        ]);
        DatabaseConnection::initializeTable(Table::PackageHistory, ['hardware_id', 'pkg_id'], [
            [1, 20],
            [2, 20],
        ]);
        DatabaseConnection::initializeTable(Table::WindowsProductKeys, ['hardware_id'], [
            [1],
            [2],
        ]);
        DatabaseConnection::initializeTable('devices', ['hardware_id', 'name', 'ivalue'], [
            [1, 'name1', 30],
            [2, 'name2', 30],
        ]);
    }

    private function assertClientDeleted(Connection $connection)
    {
        $this->assertEquals([1], $this->fetchColumn($connection, Table::ClientTable, 'id'));
        $this->assertEquals([1], $this->fetchColumn($connection, Table::AndroidEnvironments, 'hardware_id'));
        $this->assertEquals([1], $this->fetchColumn($connection, Table::ClientSystemInfo, 'hardware_id'));
        $this->assertEquals([1], $this->fetchColumn($connection, Table::CustomFields, 'hardware_id'));
        $this->assertEquals([1], $this->fetchColumn($connection, Table::GroupMemberships, 'hardware_id'));
        $this->assertEquals([1], $this->fetchColumn($connection, Table::PackageHistory, 'hardware_id'));
        $this->assertEquals([1], $this->fetchColumn($connection, Table::WindowsProductKeys, 'hardware_id'));
        $this->assertEquals([1], $this->fetchColumn($connection, 'devices', 'hardware_id'));
    }

    private function assertNotClientDeleted(Connection $connection)
    {
        $this->assertEquals([1, 2], $this->fetchColumn($connection, Table::ClientTable, 'id'));
        $this->assertEquals([1, 2], $this->fetchColumn($connection, Table::AndroidEnvironments, 'hardware_id'));
        $this->assertEquals([1, 2], $this->fetchColumn($connection, Table::ClientSystemInfo, 'hardware_id'));
        $this->assertEquals([1, 2], $this->fetchColumn($connection, Table::CustomFields, 'hardware_id'));
        $this->assertEquals([1, 2], $this->fetchColumn($connection, Table::GroupMemberships, 'hardware_id'));
        $this->assertEquals([1, 2], $this->fetchColumn($connection, Table::PackageHistory, 'hardware_id'));
        $this->assertEquals([1, 2], $this->fetchColumn($connection, Table::WindowsProductKeys, 'hardware_id'));
        $this->assertEquals([1, 2], $this->fetchColumn($connection, 'devices', 'hardware_id'));
    }

    private function assertInterfacesDeleted(Connection $connection)
    {
        $this->assertEquals(
            ['02:00:00:00:01:01'],
            $this->fetchColumn($connection, Table::NetworkDevicesIdentified, 'macaddr')
        );
        $this->assertEquals(
            ['02:00:00:00:01:01'],
            $this->fetchColumn($connection, Table::NetworkDevicesScanned, 'mac')
        );
    }

    private function assertNotInterfacesDeleted(Connection $connection)
    {
        $this->assertEquals(
            ['02:00:00:00:01:01', '02:00:00:00:02:01', '02:00:00:00:02:02'],
            $this->fetchColumn($connection, Table::NetworkDevicesIdentified, 'macaddr')
        );
        $this->assertEquals(
            ['02:00:00:00:01:01', '02:00:00:00:02:01', '02:00:00:00:02:02'],
            $this->fetchColumn($connection, Table::NetworkDevicesScanned, 'mac')
        );
    }

    public function testDeleteNoDeleteInterfaces()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $this->setupDelete();

            $clientId = 2;
            $client = $this->createMock(Client::class);
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->once())->method('deleteItems')->with($clientId);

            $locks = $this->createMock(Locks::class);
            $locks->expects($this->once())->method('lock')->willReturn(true);
            $locks->expects($this->once())->method('release');

            $clients = $this->createClients($connection, $itemManager, locks: $locks);
            $clients->delete($client, deleteInterfaces: false);

            $this->assertClientDeleted($connection);
            $this->assertNotInterfacesDeleted($connection);
        });
    }

    public function testDeleteDeleteInterfaces()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $this->setupDelete();

            $clientId = 2;
            $client = $this->createMock(Client::class);
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->once())->method('deleteItems')->with($clientId);

            $locks = $this->createMock(Locks::class);
            $locks->expects($this->once())->method('lock')->willReturn(true);
            $locks->expects($this->once())->method('release');

            $clients = $this->createClients($connection, $itemManager, locks: $locks);
            $clients->delete($client, deleteInterfaces: true);

            $this->assertClientDeleted($connection);
            $this->assertInterfacesDeleted($connection);
        });
    }

    public function testDeleteLockingFailure()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $this->setupDelete();

            $clientId = 2;
            $client = $this->createMock(Client::class);
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->never())->method('deleteItems');

            $locks = $this->createMock(Locks::class);
            $locks->expects($this->once())->method('lock')->willReturn(false);
            $locks->expects($this->never())->method('release');

            $clients = $this->createClients($connection, $itemManager, locks: $locks);
            try {
                $clients->delete($client, deleteInterfaces: true);
                $this->fail('Expected Exception was not thrown');
            } catch (RuntimeException $exception) {
                $this->assertEquals('Could not lock client for deletion', $exception->getMessage());
            }

            $this->assertNotClientDeleted($connection);
            $this->assertNotInterfacesDeleted($connection);
        });
    }

    public function testDeleteExceptionHandling()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $this->setupDelete();

            $clientId = 2;
            $client = $this->createMock(Client::class);
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->once())->method('deleteItems')->willThrowException(new Exception('test'));

            $locks = $this->createMock(Locks::class);
            $locks->expects($this->once())->method('lock')->willReturn(true);
            $locks->expects($this->once())->method('release');

            $clients = $this->createClients($connection, $itemManager, locks: $locks);
            try {
                $clients->delete($client, deleteInterfaces: true);
                $this->fail('Expected Exception was not thrown');
            } catch (Exception $exception) {
                $this->assertEquals('test', $exception->getMessage());
            }

            $this->assertNotClientDeleted($connection);
            $this->assertNotInterfacesDeleted($connection);
        });
    }

    public function testGetGroupIds()
    {
        $client = $this->createStub(Client::class);

        $clients = $this->createPartialMock(Clients::class, ['getGroupMemberships']);
        $clients->method('getGroupMemberships')->with($client, Membership::Automatic, Membership::Manual)->willReturn([
            2 => Membership::Automatic,
            4 => Membership::Manual
        ]);

        $this->assertEquals([2, 4], $clients->getGroupIds($client));
    }

    public static function getGroupMembershipsProvider()
    {
        return [
            [
                [],
                [
                    1 => Membership::Manual,
                    2 => Membership::Never,
                    3 => Membership::Automatic,
                ]
            ],
            [
                [Membership::Manual, Membership::Never],
                [
                    1 => Membership::Manual,
                    2 => Membership::Never,
                ]
            ],
            [
                [Membership::Manual],
                [1 => Membership::Manual],
            ],
            [
                [Membership::Never],
                [2 => Membership::Never],
            ],
            [
                [Membership::Automatic],
                [3 => Membership::Automatic],
            ],
        ];
    }

    #[DataProvider('getGroupMembershipsProvider')]
    public function testGetGroupMemberships(array $types, array $expected)
    {
        DatabaseConnection::with(function (Connection $connection) use ($types, $expected): void {
            // Start with empty table.
            DatabaseConnection::initializeTable(Table::GroupMemberships, [], []);

            $groups = $this->createMock(Groups::class);
            $groups->expects($this->once())->method('updateCache')->willReturnCallback(function () {
                // Initialize table here to ensure that the cache is updated before querying.
                DatabaseConnection::initializeTable(Table::GroupMemberships, ['hardware_id', 'group_id', 'static'], [
                    [1, 1, 1],
                    [1, 2, 2],
                    [1, 3, 0],
                    [2, 1, 0],
                    [2, 2, 1],
                    [2, 3, 3],
                ]);
            });

            $client = $this->createStub(Client::class);
            $client->id = 1;

            $clients = $this->createClients(connection: $connection, groups: $groups);
            $memberships = $clients->getGroupMemberships($client, ...$types);
            ksort($memberships);
            $this->assertSame($expected, $memberships);
        });
    }

    public static function setGroupMembershipsNoActionProvider()
    {
        return [
            [
                [],
                [],
            ],
            [
                [],
                ['group1' => Membership::Automatic],
            ],
            [
                [2 => Membership::Automatic],
                ['group1' => Membership::Automatic],
            ],
            [
                [1 => Membership::Automatic],
                ['group1' => Membership::Automatic],
            ],
            [
                [1 => Membership::Manual],
                ['group1' => Membership::Manual],
            ],
            [
                [1 => Membership::Never],
                ['group1' => Membership::Never],
            ],
            [
                [],
                ['ignore' => Membership::Manual],
            ],
        ];
    }

    #[DataProvider('setGroupMembershipsNoActionProvider')]
    public function testSetGroupMembershipsNoAction(array $oldMemberships, array $newMemberships)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('insert');
        $connection->expects($this->never())->method('update');
        $connection->expects($this->never())->method('delete');

        $group1 = new Group();
        $group1->id = 1;
        $group1->name = 'name1';

        $group2 = new Group();
        $group2->id = 2;
        $group2->name = 'name2';

        $groups = $this->createMock(Groups::class);
        $groups->method('getGroups')->with()->willReturn([$group1, $group2]);

        $client = $this->createStub(Client::class);

        $clients = $this->createClientsMock(
            ['getGroupMemberships'],
            connection: $connection,
            groups: $groups,
        );
        $clients->method('getGroupMemberships')->with($client)->willReturn($oldMemberships);
        $clients->setGroupMemberships($client, $newMemberships);
    }

    public static function setGroupMembershipsInsertProvider()
    {
        return [
            [
                [],
                Membership::Manual,
            ],
            [
                [],
                Membership::Never,
            ],
            [
                [2 => Membership::Automatic->value],
                Membership::Manual,
            ],
            [
                [2 => Membership::Automatic->value],
                Membership::Never,
            ],
        ];
    }

    #[DataProvider('setGroupMembershipsInsertProvider')]
    public function testSetGroupMembershipsInsert(array $oldMemberships, Membership $newMembership)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('insert')->with(Table::GroupMemberships, [
            'hardware_id' => 42,
            'group_id' => 1,
            'static' => $newMembership->value,
        ]);
        $connection->expects($this->never())->method('update');
        $connection->expects($this->never())->method('delete');

        $group1 = new Group();
        $group1->id = 1;
        $group1->name = 'name1';

        $group2 = new Group();
        $group2->id = 2;
        $group2->name = 'name2';

        $groups = $this->createMock(Groups::class);
        $groups->method('getGroups')->with()->willReturn([$group1, $group2]);

        $client = $this->createStub(Client::class);
        $client->id = 42;

        $clients = $this->createClientsMock(
            ['getGroupMemberships'],
            connection: $connection,
            groups: $groups,
        );
        $clients->method('getGroupMemberships')->with($client)->willReturn($oldMemberships);
        $clients->setGroupMemberships($client, ['name1' => $newMembership]);
    }

    public static function setGroupMembershipsUpdateProvider()
    {
        return [
            [
                Membership::Automatic,
                Membership::Manual,
            ],
            [
                Membership::Automatic,
                Membership::Never,
            ],
            [
                Membership::Manual,
                Membership::Never,
            ],
            [
                Membership::Never,
                Membership::Manual,
            ],
        ];
    }

    #[DataProvider('setGroupMembershipsUpdateProvider')]
    public function testSetGroupMembershipsUpdate(Membership $oldMembership, Membership $newMembership)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('insert');
        $connection->expects($this->once())->method('update')->with(
            Table::GroupMemberships,
            ['static' => $newMembership->value],
            [
                'hardware_id' => 42,
                'group_id' => 1,
            ]
        );
        $connection->expects($this->never())->method('delete');

        $group1 = new Group();
        $group1->id = 1;
        $group1->name = 'name1';

        $group2 = new Group();
        $group2->id = 2;
        $group2->name = 'name2';

        $groups = $this->createMock(Groups::class);
        $groups->method('getGroups')->with()->willReturn([$group1, $group2]);

        $client = $this->createMock(Client::class);
        $client->id = 42;

        $clients = $this->createClientsMock(
            ['getGroupMemberships'],
            connection: $connection,
            groups: $groups,
        );
        $clients->method('getGroupMemberships')->with($client)->willReturn([1 => $oldMembership]);
        $clients->setGroupMemberships($client, ['name1' => $newMembership]);
    }

    public static function setGroupMembershipsDeleteProvider()
    {
        return [
            [Membership::Manual],
            [Membership::Never],
        ];
    }

    #[DataProvider('setGroupMembershipsDeleteProvider')]
    public function testSetGroupMembershipsDelete(Membership $oldMembership)
    {
        $group1 = new Group();
        $group1->id = 1;
        $group1->name = 'name1';

        $group2 = new Group();
        $group2->id = 2;
        $group2->name = 'name2';

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('insert');
        $connection->expects($this->never())->method('update');
        $connection->expects($this->once())->method('delete')->with(
            Table::GroupMemberships,
            [
                'hardware_id' => 42,
                'group_id' => 1,
            ],
        );


        $groups = $this->createMock(Groups::class);
        $groups->expects($this->once())->method('updateMemberships')->with($group1, true);
        $groups->method('getGroups')->with()->willReturn([$group1, $group2]);

        $client = $this->createMock(Client::class);
        $client->id = 42;

        $clients = $this->createClientsMock(
            ['getGroupMemberships'],
            connection: $connection,
            groups: $groups,
        );
        $clients->method('getGroupMemberships')->with($client)->willReturn([1 => $oldMembership]);
        $clients->setGroupMemberships($client, ['name1' => Membership::Automatic]);
    }

    public function testSetGroupMembershipsMixedKeys()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('insert')->once()->with(
            Table::GroupMemberships,
            [
                'hardware_id' => 42,
                'group_id' => 1,
                'static' => Membership::Manual->value,
            ]
        );
        $connection->shouldReceive('insert')->once()->with(
            Table::GroupMemberships,
            [
                'hardware_id' => 42,
                'group_id' => 3,
                'static' => Membership::Never->value,
            ],
        );
        $connection->shouldNotReceive('update');
        $connection->shouldNotReceive('delete');

        $group1 = new Group();
        $group1->id = 1;
        $group1->name = 'name1';

        $group2 = new Group();
        $group2->id = 2;
        $group2->name = 'name2';

        $group3 = new Group();
        $group3->id = 3;
        $group3->name = 'name3';

        $groups = $this->createMock(Groups::class);
        $groups->method('getGroups')->with()->willReturn([$group1, $group2, $group3]);

        $client = $this->createMock(Client::class);
        $client->id = 42;

        $clients = $this->createClientsMock(
            ['getGroupMemberships'],
            connection: $connection,
            groups: $groups,
        );
        $clients->method('getGroupMemberships')->with($client)->willReturn([2 => Membership::Manual]);
        $clients->setGroupMemberships(
            $client,
            [
                1 => Membership::Manual,
                'name2' => Membership::Manual,
                'name3' => Membership::Never,
            ]
        );
    }
}
