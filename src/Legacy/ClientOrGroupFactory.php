<?php

namespace Braintacle\Legacy;

use DI\Factory\RequestedEntry;
use Model\ClientOrGroup;
use Psr\Container\ContainerInterface;

/**
 * Factory for Client and Group objects.
 */
class ClientOrGroupFactory
{
    public function __invoke(RequestedEntry $requestedEntry, ContainerInterface $container): ClientOrGroup
    {
        $class = $requestedEntry->getName();
        $clientOrGroup = new $class();
        assert($clientOrGroup instanceof ClientOrGroup);
        $clientOrGroup->setContainer($container);

        return $clientOrGroup;
    }
}
