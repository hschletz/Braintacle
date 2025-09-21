<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\Clients;
use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Database\Table;
use Braintacle\Test\DatabaseConnection;
use Doctrine\DBAL\Connection;
use Exception;
use Model\Client\Client;
use Model\Client\ItemManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Clients::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
final class ClientsTest extends TestCase
{
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
            $client->expects($this->once())->method('lock')->willReturn(true);
            $client->expects($this->once())->method('unlock');
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->once())->method('deleteItems')->with($clientId);

            $clients = new Clients($connection, $itemManager);
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
            $client->expects($this->once())->method('lock')->willReturn(true);
            $client->expects($this->once())->method('unlock');
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->once())->method('deleteItems')->with($clientId);

            $clients = new Clients($connection, $itemManager);
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
            $client->expects($this->once())->method('lock')->willReturn(false);
            $client->expects($this->never())->method('unlock');
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->never())->method('deleteItems');

            $clients = new Clients($connection, $itemManager);
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
            $client->expects($this->once())->method('lock')->willReturn(true);
            $client->expects($this->once())->method('unlock');
            $client->id = $clientId;

            $itemManager = $this->createMock(ItemManager::class);
            $itemManager->expects($this->once())->method('deleteItems')->willThrowException(new Exception('test'));

            $clients = new Clients($connection, $itemManager);
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
}
