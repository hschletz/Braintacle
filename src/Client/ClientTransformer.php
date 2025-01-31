<?php

namespace Braintacle\Client;

use Formotron\Transformer;
use Model\Client\ClientManager;

/**
 * Transform client ID to Client object.
 */
class ClientTransformer implements Transformer
{
    public function __construct(private ClientManager $clientManager)
    {
    }

    public function transform(mixed $value, array $args): mixed
    {
        return $this->clientManager->getClient($value);
    }
}
