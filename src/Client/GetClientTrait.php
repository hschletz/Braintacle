<?php

namespace Braintacle\Client;

use InvalidArgumentException;
use Model\Client\Client;
use Model\Client\ClientManager;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Get requested client from "id" URL path segment.
 */
trait GetClientTrait
{
    private ClientManager $clientManager;

    public function getClientId(ServerRequestInterface $request): int
    {
        return $request->getAttribute('id') ?? throw new InvalidArgumentException('"id" attribute missing in route');
    }

    public function getClient(ServerRequestInterface $request): Client
    {
        return $this->clientManager->getClient($this->getClientId($request));
    }
}
