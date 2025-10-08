<?php

namespace Braintacle\Legacy;

use Model\Client\Client;
use Psr\Container\ContainerInterface;

/**
 * Factory for Client objects.
 */
class ClientFactory
{
    public function __invoke(ContainerInterface $container): Client
    {
        $client = new Client();
        $client->setContainer($container);

        return $client;
    }
}
