<?php

namespace Braintacle\Client;

use Formotron\Attribute\Key;
use Formotron\Attribute\Transform;
use Model\Client\Client;

/**
 * URI route arguments for client actions.
 */
class ClientRequestParameters
{
    #[Key('id')]
    #[Transform(ClientTransformer::class)]
    public Client $client;
}
