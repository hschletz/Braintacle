<?php

namespace Braintacle\Client;

use Braintacle\Client\Registry\RegistryData;
use Braintacle\Database\Table;
use Doctrine\DBAL\Connection;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Client\Item\NetworkInterface;

/**
 * Retrieve details for specific clients.
 */
final class ClientDetails
{
    public function __construct(
        private Connection $connection,
        private DataProcessor $dataProcessor,
    ) {}

    /**
     * Get list of all networks this client is connected to.
     *
     * @return string[]
     */
    public function getNetworks(Client $client): array
    {
        $networks = [];
        /** @var NetworkInterface $interface */
        foreach ($client->getItems('NetworkInterface', 'Subnet') as $interface) {
            $network = $interface->subnet;
            if ($network !== null && !in_array($network, $networks)) {
                $networks[] = $network;
            }
        }

        return $networks;
    }

    public function getOsType(Client $client): OsType
    {
        if ($client->windows) {
            return OsType::Windows;
        } elseif ($client->android) {
            return OsType::Android;
        } else {
            return OsType::Unix;
        }
    }

    /**
     * @return iterable<RegistryData>
     */
    public function getRegistryData(Client $client): iterable
    {
        $result = $this->connection
            ->createQueryBuilder()
            ->select('rd.name', 'rd.regvalue AS data', 'rvd.regtree', 'rvd.regkey', 'rvd.regvalue AS value_name')
            ->from(Table::RegistryData, 'rd')
            ->join('rd', Table::RegistryValueDefinitions, 'rvd', 'rd.name = rvd.name')
            ->where('rd.hardware_id = :client')
            ->addOrderBy('rd.name')
            ->addOrderBy('rd.regvalue')
            ->setParameter('client', $client->id)
            ->executeQuery()
            ->iterateAssociative();

        return $this->dataProcessor->iterate($result, RegistryData::class);
    }
}
