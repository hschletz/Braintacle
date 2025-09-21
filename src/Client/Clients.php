<?php

namespace Braintacle\Client;

use Braintacle\Database\Table;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Model\Client\Client;
use Model\Client\ItemManager;
use RuntimeException;
use Throwable;

/**
 * Manage clients.
 */
final class Clients
{
    public function __construct(
        private Connection $connection,
        private ItemManager $itemManager,
    ) {}

    /**
     * @param bool $deleteInterfaces Delete interfaces from scanned interfaces
     * @throws RuntimeException if the client is locked by another instance
     */
    public function delete(Client $client, bool $deleteInterfaces): void
    {
        if (!$client->lock()) {
            throw new RuntimeException('Could not lock client for deletion');
        }

        $id = $client->id;
        $this->connection->beginTransaction();
        try {
            // If requested, delete client's network interfaces from the list of
            // scanned interfaces. Also delete any manually entered description.
            if ($deleteInterfaces) {
                $macAddresses = $this->connection
                    ->createQueryBuilder()
                    ->select('macaddr')
                    ->from(Table::NetworkInterfaces)
                    ->where('hardware_id = :id')
                    ->setParameter('id', $id)
                    ->fetchFirstColumn();
                $this->connection
                    ->createQueryBuilder()
                    ->delete(Table::NetworkDevicesIdentified)
                    ->where('macaddr IN (:macaddr)')
                    ->setParameter('macaddr', $macAddresses, ArrayParameterType::STRING)
                    ->executeStatement();
                $this->connection
                    ->createQueryBuilder()
                    ->delete(Table::NetworkDevicesScanned)
                    ->where('mac IN (:macaddr)')
                    ->setParameter('macaddr', $macAddresses, ArrayParameterType::STRING)
                    ->executeStatement();
            }

            // Delete rows from foreign tables
            $foreignTables = [
                Table::AndroidEnvironments,
                Table::ClientSystemInfo,
                Table::CustomFields,
                Table::GroupMemberships,
                Table::PackageHistory,
                Table::WindowsProductKeys,
                'devices',
            ];
            foreach ($foreignTables as $table) {
                $this->connection->delete($table, ['hardware_id' => $id]);
            }
            $this->itemManager->deleteItems($id);

            // Delete row in clients table
            $this->connection->delete(Table::ClientTable, ['id' => $id]);

            $this->connection->commit();
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        } finally {
            $client->unlock();
        }
    }
}
