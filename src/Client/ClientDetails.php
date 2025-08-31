<?php

namespace Braintacle\Client;

use Model\Client\Client;
use Model\Client\Item\NetworkInterface;

/**
 * Retrieve details for specific clients.
 */
final class ClientDetails
{
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
}
