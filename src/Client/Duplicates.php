<?php

namespace Braintacle\Client;

use Braintacle\Configuration\ClientConfig;
use Braintacle\Database\Table;
use Braintacle\Group\Membership;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Model\Client\Client;
use Model\Client\ClientManager;
use Model\Client\DuplicatesManager;
use Model\SoftwareManager;
use RuntimeException;
use Throwable;

/**
 * Manage client duplicates.
 */
final class Duplicates
{
    public function __construct(
        private Connection $connection,
        private ClientConfig $clientConfig,
        private ClientManager $clientManager,
        private Clients $clients,
        private SoftwareManager $softwareManager,
    ) {}

    /**
     * Merge clients.
     *
     * Eliminate duplicate clients in the database. Based on the last contact,
     * the newest entry is preserved. All older entries are deleted. Some
     * information from the older entries can be preserved on the remaining
     * client.
     *
     * @param int[] $clientIds IDs of clients to merge
     * @param string[] $options Attributes to merge, see MERGE_* constants
     * @throws RuntimeException if an affected client cannot be locked
     */
    public function merge(array $clientIds, array $options): void
    {
        // Remove duplicate IDs
        $clientIds = array_unique($clientIds);
        if (count($clientIds) < 2) {
            return; // Nothing to do
        }

        $this->connection->beginTransaction();
        try {
            // Lock all given clients and create a list sorted by lastContactDate.
            $clients = [];
            foreach ($clientIds as $id) {
                $client = $this->clientManager->getClient($id);
                if (!$client->lock()) {
                    throw new RuntimeException("Cannot lock client $id");
                }
                $timestamp = $client->lastContactDate->getTimestamp();
                if (isset($clients[$timestamp])) {
                    throw new RuntimeException('Cannot merge because clients have identical lastContactDate');
                }
                $clients[$timestamp] = $client;
            }
            ksort($clients);
            // Now that the list is sorted, renumber the indices.
            $clients = array_values($clients);

            // Newest client will be the only one not to be deleted, remove it from the list
            $newest = array_pop($clients);

            if (in_array(DuplicatesManager::MERGE_CONFIG, $options)) {
                $this->mergeConfig($newest, $clients);
            }
            if (in_array(DuplicatesManager::MERGE_CUSTOM_FIELDS, $options)) {
                $this->mergeCustomFields($newest, $clients);
            }
            if (in_array(DuplicatesManager::MERGE_GROUPS, $options)) {
                $this->mergeGroups($newest, $clients);
            }
            if (in_array(DuplicatesManager::MERGE_PACKAGES, $options)) {
                $this->mergePackages($newest, $clients);
            }
            if (in_array(DuplicatesManager::MERGE_PRODUCT_KEY, $options)) {
                $this->mergeProductKey($newest, $clients);
            }

            // Delete all older clients
            foreach ($clients as $client) {
                $this->clients->delete($client, deleteInterfaces: false);
            }
            // Unlock remaining client
            $newest->unlock();
            $this->connection->commit();
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }

    /**
     * Merge config on newest client with values from older clients.
     *
     * If a config option is not set on the newest client, set it to a value
     * configured on an older client (if any). If multiple older clients have a
     * value configured, the value from the most recent client is used.
     *
     * @param Client[] $olderClients sorted by lastContactDate (ascending)
     */
    public function mergeConfig(Client $newestClient, array $olderClients): void
    {
        $options = [];
        foreach (array_reverse($olderClients) as $client) {
            // Add options that are not present yet
            $options += $this->clientConfig->getExplicitConfig($client);
        }
        // Remove options that are present on the newest client
        $options = array_diff_key($options, $this->clientConfig->getExplicitConfig($newestClient));

        foreach ($options as $option => $value) {
            $this->clientConfig->setOption($newestClient, $option, $value);
        }
    }

    /**
     * Overwrite custom fields on newest client with values from oldest client.
     *
     * @param Client[] $olderClients sorted by lastContactDate (ascending)
     */
    public function mergeCustomFields(Client $newestClient, array $olderClients): void
    {
        $newestClient->setCustomFields($olderClients[0]->customFields);
    }

    /**
     * Merge manual group memberships from older clients into newest client.
     *
     * If clients have different membership types for the same group, the
     * resulting membership type is undefined.
     *
     * @param Client[] $olderClients sorted by lastContactDate (ascending)
     */
    public function mergeGroups(Client $newestClient, array $olderClients): void
    {
        $groupList = [];
        foreach ($olderClients as $client) {
            $groupList += $this->clients->getGroupMemberships($client, Membership::Manual, Membership::Never);
        }
        $this->clients->setGroupMemberships($newestClient, $groupList);
    }

    /**
     * Add missing package assignments from older clients on the newest client.
     *
     * @param Client[] $olderClients sorted by lastContactDate (ascending)
     */
    public function mergePackages(Client $newestClient, array $olderClients): void
    {
        $id = $newestClient->id;

        // Exclude packages that are already assigned.
        $excludedPackages = $this->connection
            ->createQueryBuilder()
            ->select('ivalue')
            ->from(Table::PackageAssignments)
            ->where('hardware_id = :id', "name = 'DOWNLOAD'")
            ->setParameter('id', $id)
            ->fetchFirstColumn();

        foreach ($olderClients as $client) {
            // Update the client IDs directly.
            $update = $this->connection
                ->createQueryBuilder()
                ->update(Table::PackageAssignments)
                ->set('hardware_id', $id)
                ->where(
                    'hardware_id = :id',
                    "name != 'DOWNLOAD_SWITCH'",
                    "name LIKE 'DOWNLOAD%'",
                )
                ->setParameter('id', $client->id);
            if ($excludedPackages) {
                $update->andWhere('ivalue NOT IN (:excluded)')->setParameter(
                    'excluded',
                    $excludedPackages,
                    ArrayParameterType::INTEGER,
                );
            }
            $update->executeQuery();
        }
    }

    /**
     * Set newest client's Windows manual product key to the newest key of all given clients.
     *
     * @param Client[] $olderClients sorted by lastContactDate (ascending)
     */
    public function mergeProductKey(Client $newestClient, array $olderClients): void
    {
        if (!$newestClient->windows) {
            return;
        }
        if ($newestClient->windows->manualProductKey) {
            return;
        }
        // Iterate over all clients, newest first, and pick first key found.
        foreach (array_reverse($olderClients) as $client) {
            $productKey = $client->windows?->manualProductKey;
            if ($productKey) {
                $this->softwareManager->setProductKey($newestClient, $productKey);
                return;
            }
        }
    }
}
