<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\Clients;
use Braintacle\Client\Duplicates;
use Braintacle\Configuration\ClientConfig;
use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Database\Table;
use Braintacle\Group\Membership;
use Braintacle\Test\DatabaseConnection;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\Client;
use Model\Client\ClientManager;
use Model\Client\DuplicatesManager;
use Model\Client\WindowsInstallation;
use Model\SoftwareManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Duplicates::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
final class DuplicatesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const AllOptions = [
        DuplicatesManager::MERGE_CONFIG,
        DuplicatesManager::MERGE_CUSTOM_FIELDS,
        DuplicatesManager::MERGE_GROUPS,
        DuplicatesManager::MERGE_PACKAGES,
        DuplicatesManager::MERGE_PRODUCT_KEY,
    ];
    private const MergeMethods = [
        'mergeConfig',
        'mergeCustomFields',
        'mergeGroups',
        'mergePackages',
        'mergeProductKey',
    ];

    private const PackageAssignments = [
        [2, 'DOWNLOAD', 1, 'NOTIFIED'],
        [2, 'DOWNLOAD', 2, 'NOTIFIED'],
        [2, 'DOWNLOAD_FORCE', 1, null], // should be merged too
        [2, 'DOWNLOAD_SWITCH', 0, null], // should not be merged
        [3, 'DOWNLOAD', 2, 'SUCCESS'],
        [3, 'DOWNLOAD', 3, 'SUCCESS'],
    ];

    private function createDuplicates(
        ?Connection $connection = null,
        ?ClientConfig $clientConfig = null,
        ?ClientManager $clientManager = null,
        ?Clients $clients = null,
        ?SoftwareManager $softwareManager = null,
    ): Duplicates {
        return new Duplicates(
            $connection ?? $this->createStub(Connection::class),
            $clientConfig ?? $this->createStub(ClientConfig::class),
            $clientManager ?? $this->createStub(ClientManager::class),
            $clients ?? $this->createStub(Clients::class),
            $softwareManager ?? $this->createStub(SoftwareManager::class),
        );
    }

    public static function mergeNoneWithLessThan2ClientsProvider()
    {
        return [
            [[]],
            [[1]],
            [[1, 1]], // IDs get deduplicated
        ];
    }

    #[DataProvider('mergeNoneWithLessThan2ClientsProvider')]
    public function testMergeWithLessThan2Clients(array $clientIds)
    {
        $duplicates = $this->createPartialMock(Duplicates::class, self::MergeMethods);
        $duplicates->expects($this->never())->method('mergeConfig');
        $duplicates->expects($this->never())->method('mergeCustomFields');
        $duplicates->expects($this->never())->method('mergeGroups');
        $duplicates->expects($this->never())->method('mergePackages');
        $duplicates->expects($this->never())->method('mergeProductKey');

        $duplicates->merge($clientIds, self::AllOptions);
    }

    public function testMergeLockingError()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot lock client 1');

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');
        $connection->expects($this->never())->method('commit');

        $client = $this->createStub(Client::class);
        $client->method('lock')->willReturn(false);

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->method('getClient')->willReturn($client);

        $clients = $this->createMock(Clients::class);
        $clients->expects($this->never())->method('delete');

        $duplicates = $this
            ->getMockBuilder(Duplicates::class)
            ->onlyMethods(self::MergeMethods)
            ->setConstructorArgs([
                $connection,
                $this->createStub(ClientConfig::class),
                $clientManager,
                $clients,
                $this->createStub(SoftwareManager::class),
            ])->getMock();
        $duplicates->expects($this->never())->method('mergeConfig');
        $duplicates->expects($this->never())->method('mergeCustomFields');
        $duplicates->expects($this->never())->method('mergeGroups');
        $duplicates->expects($this->never())->method('mergePackages');
        $duplicates->expects($this->never())->method('mergeProductKey');

        $duplicates->merge([1, 2], self::AllOptions);
    }

    public function testMergeThrowsOnIdenticalTimestamps()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot merge because clients have identical lastContactDate');

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');
        $connection->expects($this->never())->method('commit');

        $date = new DateTimeImmutable();

        $client1 = $this->createStub(Client::class);
        $client1->method('lock')->willReturn(true);
        $client1->lastContactDate = $date;

        $client2 = $this->createStub(Client::class);
        $client2->method('lock')->willReturn(true);
        $client2->lastContactDate = $date;

        $clientManager = $this->createStub(ClientManager::class);
        $clientManager->method('getClient')->willReturnMap([
            [1, $client1],
            [2, $client2],
        ]);

        $clients = $this->createMock(Clients::class);
        $clients->expects($this->never())->method('delete');

        $duplicates = $this
            ->getMockBuilder(Duplicates::class)
            ->onlyMethods(self::MergeMethods)
            ->setConstructorArgs([
                $connection,
                $this->createStub(ClientConfig::class),
                $clientManager,
                $clients,
                $this->createStub(SoftwareManager::class),
            ])->getMock();

        $duplicates->expects($this->never())->method('mergeConfig');
        $duplicates->expects($this->never())->method('mergeCustomFields');
        $duplicates->expects($this->never())->method('mergeGroups');
        $duplicates->expects($this->never())->method('mergePackages');
        $duplicates->expects($this->never())->method('mergeProductKey');

        $duplicates->merge([1, 2], self::AllOptions);
    }

    public static function mergeWithoutMergingAttributesProvider()
    {
        return [
            'standard order' => [[1, 2, 3]],
            'reversed order' => [[3, 2, 1]], // Test reversed order - should not make a difference
            'test deduplication' => [[1, 1, 2, 2, 3, 3]],
        ];
    }

    #[DataProvider('mergeWithoutMergingAttributesProvider')]
    public function testMergeWithoutMergingAttributes(array $clientIds)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $connection->expects($this->never())->method('rollBack');

        $client1 = $this->createMock(Client::class);
        $client1->method('lock')->willReturn(true);
        $client1->expects($this->never())->method('unlock');
        $client1->lastContactDate = new DateTimeImmutable('2025-09-16T17:00:01');

        $client2 = $this->createMock(Client::class);
        $client2->method('lock')->willReturn(true);
        $client2->expects($this->never())->method('unlock');
        $client2->lastContactDate = new DateTimeImmutable('2025-09-16T17:00:02');

        // The newest client that gets preserved
        $client3 = $this->createMock(Client::class);
        $client3->method('lock')->willReturn(true);
        $client3->expects($this->once())->method('unlock');
        $client3->lastContactDate = new DateTimeImmutable('2025-09-16T17:00:03');

        $clientManager = Mockery::mock(ClientManager::class);
        $clientManager->shouldReceive('getClient')->once()->with(1)->andReturn($client1);
        $clientManager->shouldReceive('getClient')->once()->with(2)->andReturn($client2);
        $clientManager->shouldReceive('getClient')->once()->with(3)->andReturn($client3);

        $clients = Mockery::mock(Clients::class);
        $clients->shouldReceive('delete')->once()->with($client1, false);
        $clients->shouldReceive('delete')->once()->with($client2, false);

        $duplicates = $this
            ->getMockBuilder(Duplicates::class)
            ->onlyMethods(self::MergeMethods)
            ->setConstructorArgs([
                $connection,
                $this->createStub(ClientConfig::class),
                $clientManager,
                $clients,
                $this->createStub(SoftwareManager::class),
            ])->getMock();

        $duplicates->expects($this->never())->method('mergeConfig');
        $duplicates->expects($this->never())->method('mergeCustomFields');
        $duplicates->expects($this->never())->method('mergeGroups');
        $duplicates->expects($this->never())->method('mergePackages');
        $duplicates->expects($this->never())->method('mergeProductKey');

        $duplicates->merge($clientIds, []);
    }

    public static function mergeWithMergingAttributesProvider()
    {
        return [
            [[DuplicatesManager::MERGE_CONFIG]],
            [[DuplicatesManager::MERGE_CUSTOM_FIELDS]],
            [[DuplicatesManager::MERGE_GROUPS]],
            [[DuplicatesManager::MERGE_PACKAGES]],
            [[DuplicatesManager::MERGE_PRODUCT_KEY]],
            [self::AllOptions]
        ];
    }

    #[DataProvider('mergeWithMergingAttributesProvider')]
    public function testMergeWithMergingAttributes(array $options)
    {
        $connection = $this->createStub(Connection::class);

        $client1 = $this->createMock(Client::class);
        $client1->method('lock')->willReturn(true);
        $client1->lastContactDate = new DateTimeImmutable('2025-09-16T19:12:01');

        $client2 = $this->createMock(Client::class);
        $client2->method('lock')->willReturn(true);
        $client2->lastContactDate = new DateTimeImmutable('2025-09-16T19:12:02');

        // The newest client that gets preserved
        $client3 = $this->createMock(Client::class);
        $client3->method('lock')->willReturn(true);
        $client3->lastContactDate = new DateTimeImmutable('2025-09-16T19:12:03');

        $clientManager = Mockery::mock(ClientManager::class);
        $clientManager->shouldReceive('getClient')->once()->with(1)->andReturn($client1);
        $clientManager->shouldReceive('getClient')->once()->with(2)->andReturn($client2);
        $clientManager->shouldReceive('getClient')->once()->with(3)->andReturn($client3);

        $duplicates = $this
            ->getMockBuilder(Duplicates::class)
            ->onlyMethods(self::MergeMethods)
            ->setConstructorArgs([
                $connection,
                $this->createStub(ClientConfig::class),
                $clientManager,
                $this->createStub(Clients::class),
                $this->createStub(SoftwareManager::class),
            ])->getMock();

        foreach (self::AllOptions as $option) {
            // Option names are identical to corresponding method names.
            if (in_array($option, $options)) {
                $duplicates->expects($this->once())->method($option)->with($client3, [$client1, $client2]);
            } else {
                $duplicates->expects($this->never())->method($option);
            }
        }

        $duplicates->merge([1, 2, 3], $options);
    }

    public function testMergeConfig()
    {
        // Test method with 2 older clients. Newest value (if not NULL) is
        // applied to setOption(). This results in 8 possible combinations:
        //
        // option  | oldest | middle | newest | result
        // -------------------------------------------
        // option0 |  null  |  null  |  null  |  null
        // option1 |  null  |  null  |   n1   |   n1
        // option2 |  null  |   m2   |  null  |   m2
        // option3 |  null  |   m3   |   n3   |   n3
        // option4 |   o4   |  null  |  null  |   o4
        // option5 |   o5   |  null  |   n5   |   n5
        // option6 |   o6   |   m6   |  null  |   m6
        // option7 |   o7   |   m7   |   n7   |   n7
        //
        // Because values from the newest client don't need to be reapplied,
        // only m2, o4 and m6 get applied.

        $newest = $this->createStub(Client::class);
        $middle = $this->createStub(Client::class);
        $oldest = $this->createStub(Client::class);

        $clientConfig = Mockery::mock(ClientConfig::class);
        $clientConfig->shouldReceive('getExplicitConfig')->with($newest)->andReturn(
            ['option1' => 'n1', 'option3' => 'n3', 'option5' => 'n5', 'option7' => 'n7']
        );
        $clientConfig->shouldReceive('getExplicitConfig')->with($middle)->andReturn(
            ['option2' => 'm2', 'option3' => 'm3', 'option6' => 'm6', 'option7' => 'm7']
        );
        $clientConfig->shouldReceive('getExplicitConfig')->with($oldest)->andReturn(
            ['option4' => 'o4', 'option5' => 'o5', 'option6' => 'o6', 'option7' => 'o7']
        );
        $clientConfig->shouldReceive('setOption')->once()->with($newest, 'option2', 'm2');
        $clientConfig->shouldReceive('setOption')->once()->with($newest, 'option4', 'o4');
        $clientConfig->shouldReceive('setOption')->once()->with($newest, 'option6', 'm6');

        $duplicates = new Duplicates(
            $this->createStub(Connection::class),
            $clientConfig,
            $this->createStub(ClientManager::class),
            $this->createStub(Clients::class),
            $this->createStub(SoftwareManager::class),
        );
        $duplicates->mergeConfig($newest, [$oldest, $middle]);
    }

    public function testMergeCustomFields()
    {
        $newestClient = $this->createMock(Client::class);
        $newestClient->expects($this->once())->method('setCustomFields')->with(['field' => 'value']);

        $client1 = $this->createMock(Client::class);
        $client1->method('__get')->with('customFields')->willReturn(['field' => 'value']);

        $client2 = $this->createMock(Client::class);
        $client2->expects($this->never())->method('__get');

        $duplicates = new Duplicates(
            $this->createStub(Connection::class),
            $this->createStub(ClientConfig::class),
            $this->createStub(ClientManager::class),
            $this->createStub(Clients::class),
            $this->createStub(SoftwareManager::class),
        );
        $duplicates->mergeCustomFields($newestClient, [$client1, $client2]);
    }

    public function testMergeGroups()
    {
        $newestClient = $this->createStub(Client::class);

        $client1 = $this->createMock(Client::class);
        $client1->method('getGroupMemberships')->with(Client::MEMBERSHIP_MANUAL)->willReturn([
            1 => Client::MEMBERSHIP_ALWAYS,
            2 => Client::MEMBERSHIP_NEVER,
        ]);

        $client2 = $this->createMock(Client::class);
        $client2->method('getGroupMemberships')->with(Client::MEMBERSHIP_MANUAL)->willReturn([
            2 => Client::MEMBERSHIP_NEVER,
            3 => Client::MEMBERSHIP_ALWAYS,
        ]);

        $clients = $this->createMock(Clients::class);
        $clients->expects($this->once())->method('setGroupMemberships')->with($newestClient, [
            1 => Membership::Manual,
            2 => Membership::Never,
            3 => Membership::Manual,
        ]);

        $duplicates = $this->createDuplicates(clients: $clients);
        $duplicates->mergeGroups($newestClient, [$client1, $client2]);
    }

    public function testMergeGroupsWithConflictingMemberships()
    {
        $newestClient = $this->createStub(Client::class);

        $client1 = $this->createMock(Client::class);
        $client1->method('getGroupMemberships')->with(Client::MEMBERSHIP_MANUAL)->willReturn([
            1 => Client::MEMBERSHIP_ALWAYS,
        ]);

        $client2 = $this->createMock(Client::class);
        $client2->method('getGroupMemberships')->with(Client::MEMBERSHIP_MANUAL)->willReturn([
            1 => Client::MEMBERSHIP_NEVER,
        ]);

        // The resulting membership type is undefinded. Just check for the
        // correct group ID and size.
        $clients = $this->createMock(Clients::class);
        $clients->expects($this->once())
            ->method('setGroupMemberships')
            ->with($newestClient, $this->logicalAnd($this->countOf(1), $this->arrayHasKey(1)));

        $duplicates = $this->createDuplicates(clients: $clients);
        $duplicates->mergeGroups($newestClient, [$client1, $client2]);
    }

    public function testMergePackages()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $columns = ['hardware_id', 'name', 'ivalue', 'tvalue'];
            DatabaseConnection::initializeTable(Table::PackageAssignments, $columns, self::PackageAssignments);

            $newestClient = $this->createStub(Client::class);
            $newestClient->id = 3;

            $olderClient = $this->createStub(Client::class);
            $olderClient->id = 2;

            $duplicates = new Duplicates(
                $connection,
                $this->createStub(ClientConfig::class),
                $this->createStub(ClientManager::class),
                $this->createStub(Clients::class),
                $this->createStub(SoftwareManager::class),
            );
            $duplicates->mergePackages($newestClient, [$olderClient]);

            // Test results for newest client only (ID 3). Remaining entries for
            // older clients will be deleted by calling code.
            $assignments = $connection
                ->createQueryBuilder()
                ->select(...$columns)
                ->from(Table::PackageAssignments)
                ->where('hardware_id = 3')
                ->addOrderBy('hardware_id')
                ->addOrderBy('name')
                ->addOrderBy('ivalue')
                ->fetchAllNumeric();

            $this->assertEquals([
                [3, 'DOWNLOAD', 1, 'NOTIFIED'],
                [3, 'DOWNLOAD', 2, 'SUCCESS'],
                [3, 'DOWNLOAD', 3, 'SUCCESS'],
                [3, 'DOWNLOAD_FORCE', 1, null],
            ], $assignments);
        });
    }

    public function testMergePackagesNoPackagesToMerge()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $columns = ['hardware_id', 'name', 'ivalue', 'tvalue'];
            DatabaseConnection::initializeTable(Table::PackageAssignments, $columns, self::PackageAssignments);

            $newestClient = $this->createStub(Client::class);
            $newestClient->id = 4;

            $olderClient = $this->createStub(Client::class);
            $olderClient->id = 1;

            $duplicates = new Duplicates(
                $connection,
                $this->createStub(ClientConfig::class),
                $this->createStub(ClientManager::class),
                $this->createStub(Clients::class),
                $this->createStub(SoftwareManager::class),
            );
            $duplicates->mergePackages($newestClient, [$olderClient]);

            // Table shlould be unchanged.
            $assignments = $connection
                ->createQueryBuilder()
                ->select(...$columns)
                ->from(Table::PackageAssignments)
                ->addOrderBy('hardware_id')
                ->addOrderBy('name')
                ->addOrderBy('ivalue')
                ->fetchAllNumeric();

            $this->assertEquals(self::PackageAssignments, $assignments);
        });
    }

    public static function mergeProductKeyNoMergeProvider()
    {
        return [
            'newest client has no Windows property' => [false, null, true, 'key1'],
            'newest client already has manual key' => [true, 'key1', true, 'key2'],
            'newest has no manual key and older has no Windows' => [true, null, false, null],
            'both have Windows property but no manual key' => [true, null, true, null],
        ];
    }

    #[DataProvider('mergeProductKeyNoMergeProvider')]
    public function testMergeProductKeyNoMerge(
        bool $isNewestWindows,
        ?string $newestKey,
        bool $isOlderWindows,
        ?string $olderKey
    ) {
        if ($isNewestWindows) {
            $windows = $this->createMock(WindowsInstallation::class);
            $windows->method('__get')->with('manualProductKey')->willReturn($newestKey);
        } else {
            $windows = null;
        }
        $newestClient = $this->createMock(Client::class);
        $newestClient->method('__get')->with('windows')->willReturn($windows);

        if ($isOlderWindows) {
            $windows = $this->createMock(WindowsInstallation::class);
            $windows->method('__get')->with('manualProductKey')->willReturn($olderKey);
        } else {
            $windows = null;
        }
        $olderClient = $this->createMock(Client::class);
        $olderClient->method('__get')->with('windows')->willReturn($windows);

        $softwareManager = $this->createMock(SoftwareManager::class);
        $softwareManager->expects($this->never())->method('setProductKey');

        $duplicates = new Duplicates(
            $this->createStub(Connection::class),
            $this->createStub(ClientConfig::class),
            $this->createStub(ClientManager::class),
            $this->createStub(Clients::class),
            $softwareManager,
        );
        $duplicates->mergeProductKey($newestClient, [$olderClient]);
    }

    public function testMergeProductKeyMerge()
    {
        $windows = $this->createStub(WindowsInstallation::class);
        $newestClient = $this->createMock(Client::class);
        $newestClient->method('__get')->with('windows')->willReturn($windows);

        // Loop is aborted before this entry is reached
        $windows1 = $this->createMock(WindowsInstallation::class);
        $windows1->method('__get')->with('manualProductKey')->willReturn('key1');
        $client1 = $this->createMock(Client::class);
        $client1->method('__get')->with('windows')->willReturn($windows1);

        // first client with key, picked
        $windows2 = $this->createMock(WindowsInstallation::class);
        $windows2->method('__get')->with('manualProductKey')->willReturn('key2');
        $client2 = $this->createMock(Client::class);
        $client2->method('__get')->with('windows')->willReturn($windows2);

        // no key, skipped
        $windows3 = $this->createMock(WindowsInstallation::class);
        $windows3->method('__get')->with('manualProductKey')->willReturn(null);
        $client3 = $this->createMock(Client::class);
        $client3->method('__get')->with('windows')->willReturn($windows3);

        // no Windows, skipped
        $client4 = $this->createMock(Client::class);
        $client4->method('__get')->with('windows')->willReturn(null);

        $softwareManager = $this->createMock(SoftwareManager::class);
        $softwareManager->expects($this->once())->method('setProductKey')->with($newestClient, 'key2');

        $duplicates = new Duplicates(
            $this->createStub(Connection::class),
            $this->createStub(ClientConfig::class),
            $this->createStub(ClientManager::class),
            $this->createStub(Clients::class),
            $softwareManager,
        );
        $duplicates->mergeProductKey($newestClient, [$client1, $client2, $client3, $client4]);
    }
}
