<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\ClientDetails;
use Braintacle\Client\OsType;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\Item\NetworkInterface;
use Model\Client\WindowsInstallation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function getOsTypeProvider()
    {
        return [
            [new WindowsInstallation(), null, OsType::Windows],
            [null, new AndroidInstallation(), OsType::Android],
            [null, null, OsType::Unix],
        ];
    }

    #[DataProvider('getOsTypeProvider')]
    public function testGetOsType(?WindowsInstallation $windows, ?AndroidInstallation $android, OsType $type)
    {
        $client = $this->createStub(Client::class);
        $client->method('__get')->willReturnMap([
            ['windows', $windows],
            ['android', $android],
        ]);
        $this->assertEquals($type, (new ClientDetails())->getOsType($client));
    }
}
