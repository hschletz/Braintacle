<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\GetClientTrait;
use InvalidArgumentException;
use Model\Client\Client;
use Model\Client\ClientManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class GetClientTraitTest extends TestCase
{
    public function testGetClientId()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('id')->willReturn(0);

        $instance = new class
        {
            use GetClientTrait;
        };
        $this->assertSame(0, $instance->getClientId($request));
    }

    public function testGetClientIdAttributeMissing()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('id')->willReturn(null);

        $instance = new class
        {
            use GetClientTrait;
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"id" attribute missing in route');

        $instance->getClientId($request);
    }

    public function testGetClient()
    {
        $client = $this->createStub(Client::class);

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->method('getClient')->with(42)->willReturn($client);

        $request = $this->createStub(ServerRequestInterface::class);
        $instance = new class ($clientManager, $request)
        {
            use GetClientTrait;

            public function __construct(
                private ClientManager $clientManager,
                private ServerRequestInterface $request,
            ) {
            }

            public function getClientId(ServerRequestInterface $request): int
            {
                assert($request === $this->request);
                return 42;
            }
        };

        $this->assertSame($client, $instance->getClient($request));
    }
}
