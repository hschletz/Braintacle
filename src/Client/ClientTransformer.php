<?php

namespace Braintacle\Client;

use Formotron\Transformer;
use Model\Client\ClientManager;
use Override;

/**
 * Transform client ID to Client object.
 */
class ClientTransformer implements Transformer
{
    public function __construct(private ClientManager $clientManager) {}

    #[Override]
    public function transform(mixed $value, array $args): mixed
    {
        return $this->clientManager->getClient($value);
    }
}
