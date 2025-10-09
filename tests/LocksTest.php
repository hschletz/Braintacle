<?php

namespace Braintacle\Test;

use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Database\Table;
use Braintacle\Group\Group;
use Braintacle\Locks;
use Braintacle\Test\DatabaseConnection;
use Braintacle\Time;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Model\Client\Client;
use Model\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

#[CoversClass(Locks::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
final class LocksTest extends TestCase
{
    private const CurrentTime = '2025-10-04 12:34:30'; // mocked database time
    private const ExistingLockTime = '2025-10-04 12:33:30'; // 60 seconds in the past
    private const NewLockTime = '2025-10-04 12:35:30'; // 60 seconds in the future
    private const ExpiredTime = '2025-10-04 12:33:29'; // expired lock, assuming 60 seconds timeout
    private const UnusedTime = '2015-06-10 18:35:56'; // unused entry for testing WHERE clause

    private const InitialLocks = [
        [1, self::ExistingLockTime],
        [2, self::UnusedTime],
    ];

    private function createLocks(
        ?Connection $connection = null,
        ?Config $config = null,
        ?LoggerInterface $logger = null,
    ): Locks {
        $time = $this->createStub(Time::class);
        $time->method('getDatabaseTime')->willReturn(
            new DateTimeImmutable(self::CurrentTime)
        );

        return new Locks(
            $connection ?? $this->createStub(Connection::class),
            $config ?? $this->createStub(Config::class),
            $time,
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }

    private function createLocksMock(
        array $methods,
        ?Connection $connection = null,
        ?Config $config = null,
        ?LoggerInterface $logger = null,
    ): MockObject | Locks {
        $time = $this->createStub(Time::class);
        $time->method('getDatabaseTime')->willReturn(
            new DateTimeImmutable(self::CurrentTime)
        );

        return $this
            ->getMockBuilder(Locks::class)
            ->onlyMethods($methods)
            ->setConstructorArgs([
                $connection ?? $this->createStub(Connection::class),
                $config ?? $this->createStub(Config::class),
                $time,
                $logger ?? $this->createStub(LoggerInterface::class),
            ])
            ->getMock();
    }

    /**
     * Compare "locks" table with dataset.
     */
    private function assertLocksTableEquals(array $expectedDataSet, Connection $connection): void
    {
        $realDataSet = $connection->fetchAllNumeric('SELECT hardware_id, since FROM locks ORDER BY hardware_id');
        $this->assertEquals($expectedDataSet, $realDataSet);
    }

    public static function lockProvider()
    {
        return [
            'no existing lock' => [
                42,
                60,
                true,
                [
                    [1, self::ExistingLockTime],
                    [2, self::UnusedTime],
                    [42, self::CurrentTime],
                ]
            ],
            'existing lock, expired' => [
                1,
                58,
                true,
                [
                    [1, self::CurrentTime],
                    [2, self::UnusedTime],
                ],
            ],
            'existing lock, not expired' => [1, 62, false, self::InitialLocks],
        ];
    }

    #[DataProvider('lockProvider')]
    public function testLock(int $id, int $timeout, bool $success, array $expected)
    {
        DatabaseConnection::with(function (Connection $connection) use ($id, $timeout, $success, $expected) {
            DatabaseConnection::initializeTable(Table::Locks, ['hardware_id', 'since'], self::InitialLocks);

            $config = $this->createMock(Config::class);
            $config->expects($this->once())->method('__get')->with('lockValidity')->willReturn($timeout);

            $client = $this->createStub(Client::class);
            $client->id = $id;

            $locks = $this->createLocksMock(['isLocked'], $connection, $config);
            $locks->method('isLocked')->with($client)->willReturn(false);

            $this->assertSame($success, $locks->lock($client));
            $this->assertLocksTableEquals($expected, $connection);
        });
    }

    public function testLockRaceCondition()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            DatabaseConnection::initializeTable(Table::Locks, ['hardware_id', 'since'], self::InitialLocks);

            $connectionMock = $this->createMock(Connection::class);
            $connectionMock->method('createQueryBuilder')->willReturnCallback(
                fn() => $connection->createQueryBuilder()
            );
            $connectionMock->expects($this->once())->method('insert')->willReturnCallback(
                function (...$args) use ($connection) {
                    // Insert twice to provoke constraint violation, simulating race condition
                    $connection->insert(...$args);
                    $connection->insert(...$args);
                }
            );

            $config = $this->createStub(Config::class);
            $config->method('__get')->willReturn(60);

            $client = $this->createStub(Client::class);
            $client->id = 42;

            $locks = $this->createLocksMock(['isLocked'], $connectionMock, $config);
            $locks->method('isLocked')->with($client)->willReturn(false);

            $this->assertFalse($locks->lock($client));
        });
    }

    public function testReleaseWithoutLock()
    {
        $group = $this->createStub(Group::class);

        $locks = $this->createLocksMock(['isLocked']);
        $locks->expects($this->once())->method('isLocked')->with($group)->willReturn(false);
        $locks->release($group);
    }

    public function testReleaseWithReleasedLock()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            DatabaseConnection::initializeTable(Table::Locks, ['hardware_id', 'since'], self::InitialLocks);

            $client = $this->createStub(Client::class);
            $client->id = 1;

            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->never())->method('warning');

            $locks = $this->createLocksMock(['isLocked'], connection: $connection, logger: $logger);
            $locks->expects($this->once())->method('isLocked')->with($client)->willReturn(true);

            $expire = new ReflectionProperty(Locks::class, 'timeouts');
            $expire->setValue($locks, [1 => new DateTimeImmutable(self::NewLockTime)]);

            $locks->release($client);
            $this->assertLocksTableEquals([[2, self::UnusedTime]], $connection);
            $this->assertEmpty($expire->getValue($locks));
        });
    }

    public function testUnlockWithExpiredLock()
    {
        DatabaseConnection::with(
            function (Connection $connection): void {
                DatabaseConnection::initializeTable(Table::Locks, ['hardware_id', 'since'], self::InitialLocks);

                $client = $this->createStub(Client::class);
                $client->id = 1;

                $logger = $this->createMock(LoggerInterface::class);
                $logger->expects($this->once())->method('warning')->with(
                    'Lock expired prematurely. Increase lock lifetime.'
                );

                $locks = $this->createLocksMock(['isLocked'], logger: $logger);
                $locks->expects($this->once())->method('isLocked')->willReturn(true);

                $expire = new ReflectionProperty(Locks::class, 'timeouts');
                $expire->setValue($locks, [1 => new DateTimeImmutable(self::ExpiredTime)]);

                $locks->release($client);

                $this->assertLocksTableEquals(self::InitialLocks, $connection);
                $this->assertEmpty($expire->getValue($locks));
            }
        );
    }

    public function testNestedLocks()
    {
        DatabaseConnection::with(
            function (Connection $connection): void {
                DatabaseConnection::initializeTable(Table::Locks, [], []);

                $config = $this->createMock(Config::class);
                $config->method('__get')->with('lockValidity')->willReturn(42);

                $client = $this->createStub(Client::class);
                $client->id = 23;

                $locks = $this->createLocks($connection, $config);

                $this->assertFalse($locks->isLocked($client));
                $this->assertTrue($locks->lock($client));
                $this->assertTrue($locks->lock($client));
                $locks->release($client);
                $this->assertTrue($locks->isLocked($client));
                $locks->release($client);
                $this->assertFalse($locks->isLocked($client));
            }
        );
    }
}
