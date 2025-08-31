<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\ClientDetails;
use Model\Client\Client;
use Model\Client\Item\NetworkInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientDetails::class)]
final class ClientDetailsTest extends TestCase
{
    public function testGetNetworks()
    {
        $network1 = $this->createMock(NetworkInterface::class);
        $network1->method('__get')->with('subnet')->willReturn(null);

        $network2 = $this->createMock(NetworkInterface::class);
        $network2->method('__get')->with('subnet')->willReturn('192.0.2.0');

        $network3 = $this->createMock(NetworkInterface::class);
        $network3->method('__get')->with('subnet')->willReturn('192.0.2.0');

        $network4 = $this->createMock(NetworkInterface::class);
        $network4->method('__get')->with('subnet')->willReturn('198.51.100.0');

        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('NetworkInterface', 'Subnet')->willReturn([
            $network1,
            $network2,
            $network3,
            $network4,
        ]);

        $this->assertEquals(
            ['192.0.2.0', '198.51.100.0'],
            (new ClientDetails())->getNetworks($client)
        );
    }
}
