<?php

namespace Braintacle\Client;

use Braintacle\Database\Table;
use Braintacle\Group\Membership;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Model\Client\Client;
use Model\Client\ItemManager;
use Model\Group\GroupManager;
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
        private GroupManager $groupManager,
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

    /**
     * Set group memberships.
     *
     * Groups which are not present in $newMemberships remain unchanged. The
     * keys can be either the integer ID or the name of the group.
     *
     * @param array<int|string,Membership> $newMemberships
     */
    public function setGroupMemberships(Client $client, array $newMemberships): void
    {
        // Build lookup tables
        $groupsById = [];
        $groupsByName = [];
        foreach ($this->groupManager->getGroups() as $group) {
            $groupsById[$group->id] = $group;
            $groupsByName[$group->name] = $group;
        }
        $oldMemberships = array_map(
            fn(int $membership) => Membership::from($membership),
            $client->getGroupMemberships(Client::MEMBERSHIP_ANY),
        );

        foreach ($newMemberships as $groupKey => $newMembership) {
            assert($newMembership instanceof Membership);
            if (is_int($groupKey)) {
                $group = $groupsById[$groupKey] ?? null;
            } else {
                $group = $groupsByName[$groupKey] ?? null;
            }
            if (!$group) {
                continue; // Ignore unknown groups
            }

            $groupId = $group->id;
            $oldMembership = $oldMemberships[$groupId] ?? null;
            if ($newMembership == Membership::Automatic) {
                if ($oldMembership != Membership::Automatic) {
                    // Delete manual membership and update group cache because
                    // the client may be a candidate for automatic membership.
                    $this->connection->delete(Table::GroupMemberships, [
                        'hardware_id' => $client->id,
                        'group_id' => $groupId,
                    ]);
                    $group->update(true);
                }
            } else { // Manual or Never
                if ($oldMembership === null) {
                    $this->connection->insert(Table::GroupMemberships, [
                        'hardware_id' => $client->id,
                        'group_id' => $groupId,
                        'static' => $newMembership->value,
                    ]);
                } elseif ($oldMembership !== $newMembership) {
                    $this->connection->update(
                        Table::GroupMemberships,
                        ['static' => $newMembership->value],
                        [
                            'hardware_id' => $client->id,
                            'group_id' => $groupId,
                        ],
                    );
                }
            }
        }
    }
}
