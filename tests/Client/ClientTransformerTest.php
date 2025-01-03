<?php

namespace Braintacle\Test\Group;

use Braintacle\Client\ClientTransformer;
use Formotron\Transformer;
use Model\Client\Client;
use Model\Client\ClientManager;
use PHPUnit\Framework\TestCase;

class ClientTransformerTest extends TestCase
{
    public function testTransform()
    {
        $client = $this->createStub(Client::class);

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->method('getClient')->with('clientId')->willReturn($client);

        $clientTransformer = new ClientTransformer($clientManager);
        $this->assertInstanceOf(Transformer::class, $clientTransformer);
        $this->assertSame($client, $clientTransformer->transform('clientId'));
    }
}
