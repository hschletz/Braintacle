<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\ClientDetails;
use Braintacle\Client\OsType;
use Braintacle\Client\Registry\AssembleKey;
use Braintacle\Client\Registry\RegistryData;
use Braintacle\Client\Registry\RootKey;
use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Database\Table;
use Braintacle\Test\Client\Registry\AssembleKeyTest;
use Braintacle\Test\DatabaseConnection;
use Braintacle\Test\DataProcessorTestTrait;
use Doctrine\DBAL\Connection;
use Formotron\DataProcessor;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\Item\NetworkInterface;
use Model\Client\WindowsInstallation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientDetails::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
#[UsesClass(AssembleKey::class)]
final class ClientDetailsTest extends TestCase
{
    use DataProcessorTestTrait;

    private function createClientDetails(
        ?Connection $connection = null,
        ?DataProcessor $dataProcessor = null,
    ) {
        return new ClientDetails(
            $connection ?? $this->createStub(Connection::class),
            $dataProcessor ?? $this->createStub(DataProcessor::class),
        );
    }

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
            ($this->createClientDetails())->getNetworks($client)
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
        $this->assertEquals($type, ($this->createClientDetails())->getOsType($client));
    }

    #[DependsOnClass(AssembleKeyTest::class)]
    public function testGetRegistryData()
    {
        DatabaseConnection::with(function (Connection $connection) {
            DatabaseConnection::initializeTable(Table::ClientTable, ['id', 'deviceid', 'name'], [
                [1, 'id1', 'name1'],
                [2, 'id2', 'name2'],
            ]);
            DatabaseConnection::initializeTable(
                Table::RegistryValueDefinitions,
                ['name', 'regtree', 'regkey', 'regvalue'],
                [
                    ['name1', RootKey::HKEY_LOCAL_MACHINE->value, 'key1', 'value1'],
                    ['name2', RootKey::HKEY_CURRENT_CONFIG->value, 'key2', 'value2'],
                ],
            );
            DatabaseConnection::initializeTable(Table::RegistryData, ['hardware_id', 'name', 'regvalue'], [
                [1, 'name2', 'data1'],
                [1, 'name1', 'data2'],
                [1, 'name1', 'data1'],
                [2, 'name1', 'data1'],
            ]);

            $dataProcessor = $this->createDataProcessor();
            $clientDetails = $this->createClientDetails(connection: $connection, dataProcessor: $dataProcessor);

            $client = new Client();
            $client->id = 1;

            /** @var RegistryData[] */
            $registryData = iterator_to_array($clientDetails->getRegistryData($client));
            $this->assertCount(3, $registryData);
            $this->assertContainsOnlyInstancesOf(RegistryData::class, $registryData);

            $this->assertEquals('name1', $registryData[0]->name);
            $this->assertEquals('HKEY_LOCAL_MACHINE\key1\value1', $registryData[0]->path);
            $this->assertEquals('data1', $registryData[0]->data);

            $this->assertEquals('name1', $registryData[1]->name);
            $this->assertEquals('HKEY_LOCAL_MACHINE\key1\value1', $registryData[1]->path);
            $this->assertEquals('data2', $registryData[1]->data);

            $this->assertEquals('name2', $registryData[2]->name);
            $this->assertEquals('HKEY_CURRENT_CONFIG\key2\value2', $registryData[2]->path);
            $this->assertEquals('data1', $registryData[2]->data);
        });
    }
}
