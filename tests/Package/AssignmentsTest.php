<?php

namespace Braintacle\Test\Package;

use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Package\Assignments;
use Braintacle\Test\DatabaseConnection;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use Model\Client\Client;
use Model\Package\Assignment;
use Model\Package\Package;
use Model\Package\PackageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use RuntimeException;

#[CoversClass(Assignments::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
class AssignmentsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function createAssignments(
        ?Connection $connection = null,
        ?ClockInterface $clock = null,
        ?PackageManager $packageManager = null,
    ) {
        return new Assignments(
            $connection ?? $this->createStub(Connection::class),
            $clock ?? $this->createStub(ClockInterface::class),
            $packageManager ?? $this->createStub(PackageManager::class),
        );
    }

    public function testAssignPackage()
    {
        $packageName = 'packageName';
        $packageId = 42;
        $targetId = 23;

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('insert')->with('devices', [
            'hardware_id' => $targetId,
            'name' => 'DOWNLOAD',
            'ivalue' => $packageId,
            'tvalue' => Assignment::PENDING,
            'comments' => 'Wed Jun 05 19:58:21 2024',
        ]);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2024-06-05T19:58:21'));

        $package = new Package();
        $package->id = $packageId;

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getPackage')->with($packageName)->willReturn($package);

        $target = new Client();
        $target->id = $targetId;

        $assignments = $this->createAssignments($connection, $clock, $packageManager);
        $assignments->assignPackage($packageName, $target);
    }

    public function testAssignPackages()
    {
        $target = new Client();

        /** @var Mock|Assignments */
        $packageManager = Mockery::mock(Assignments::class)->makePartial();
        $packageManager->shouldReceive('assignPackage')->once()->with('package1', $target);
        $packageManager->shouldReceive('assignPackage')->once()->with('package2', $target);
        $packageManager->shouldNotReceive('assignPackage')->with(Mockery::any(), Mockery::any());

        $packageManager->assignPackages(['package1', 'package2'], $target);
    }

    public function testUnassignPackage()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $packageName = 'package_name';
            $packageId = 10;
            $targetId = 1;

            DatabaseConnection::initializeTable('devices', ['hardware_id', 'name', 'ivalue'], [
                [$targetId, 'DOWNLOAD', 2], // preserved because ivalue != $packageId
                [$targetId, 'DOWNLOAD', $packageId], // deleted
                [$targetId, 'DOWNLOAD_suffix', $packageId], // deleted
                [$targetId, 'OTHER', $packageId], // preserved because name != DOWNLOAD%
                [2, 'DOWNLOAD', $packageId], // preserved because hardware_id =! $targetId
            ]);

            $package = new Package();
            $package->id = $packageId;

            $packageManager = $this->createMock(PackageManager::class);
            $packageManager->method('getPackage')->with($packageName)->willReturn($package);

            $target = new Client();
            $target->id = 1;

            $assignments = $this->createAssignments(connection: $connection, packageManager: $packageManager);
            $assignments->unassignPackage($packageName, $target);

            $result = $connection->fetchAllNumeric(
                'SELECT hardware_id, name, ivalue FROM devices ORDER BY hardware_id, name, ivalue'
            );
            $this->assertEquals([
                [$targetId, 'DOWNLOAD', 2],
                [$targetId, 'OTHER', $packageId],
                [2, 'DOWNLOAD', $packageId],
            ], $result);
        });
    }

    public static function resetPackageProvider()
    {
        return [
            // Package has never been reset before (no DOWNLOAD_FORCE row exists yet)
            [1, [
                [1, 'DOWNLOAD', 1, null, 'Sat May 27 19:39:25 2017'], // reset
                [1, 'DOWNLOAD', 2, 'NOTIFIED', 'Tue Dec 30 19:01:23 2014'], // unchanged - package != 1
                [1, 'DOWNLOAD_FORCE', 1, '1', null], // added
                [1, 'DOWNLOAD_FORCE', 2, '1', null], // unchanged - package != 1
                [1, 'OTHER', 1, null, null], // unchanged - action != DOWNLOAD
                [2, 'DOWNLOAD', 1, 'SUCCESS', 'Tue Dec 30 19:01:23 2014'], // unchanged - target != 1
            ]],
            // Package has been reset before (DOWNLOAD_FORCE row already exists)
            [2, [
                [1, 'DOWNLOAD', 1, 'SUCCESS', 'Tue Dec 30 19:02:23 2014'], // unchanged - package != 2
                [1, 'DOWNLOAD', 2, null, 'Sat May 27 19:39:25 2017'], // reset
                [1, 'DOWNLOAD_FORCE', 2, '1', null], // unchanged - relevant row, already exists
                [1, 'OTHER', 1, null, null], // unchanged - package != 2
                [2, 'DOWNLOAD', 2, 'SUCCESS', 'Tue Dec 30 19:01:23 2014'], // unchanged - target != 1
            ]],
        ];
    }

    #[DataProvider('resetPackageProvider')]
    public function testResetPackage($packageId, $dataset)
    {
        DatabaseConnection::with(function (Connection $connection) use ($packageId, $dataset): void {
            $targetId = 1;
            $packageName = 'packageName';

            DatabaseConnection::initializeTable('devices', ['hardware_id', 'name', 'ivalue', 'tvalue', 'comments'], [
                [1, 'DOWNLOAD', 1, 'SUCCESS', 'Tue Dec 30 19:02:23 2014'],
                [1, 'DOWNLOAD', 2, 'NOTIFIED', 'Tue Dec 30 19:01:23 2014'],
                [1, 'DOWNLOAD_FORCE', 2, '1', null],
                [1, 'OTHER', 1, null, null],
                [2, 'DOWNLOAD', $packageId, 'SUCCESS', 'Tue Dec 30 19:01:23 2014'],
            ]);

            $package = new Package();
            $package->id = $packageId;

            $packageManager = $this->createMock(PackageManager::class);
            $packageManager->method('getPackage')->with($packageName)->willReturn($package);

            $clock = $this->createStub(ClockInterface::class);
            $clock->method('now')->willReturn(new DateTimeImmutable('2017-05-27T19:39:25'));

            $target = new Client();
            $target->id = $targetId;

            $assignments = $this->createAssignments($connection, $clock, $packageManager);
            $assignments->resetPackage($packageName, $target);

            $this->assertEquals(
                $dataset,
                $connection->fetchAllNumeric(
                    'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ORDER BY hardware_id, name, ivalue'
                ),
            );
        });
    }

    public function testResetPackageNotAssigned()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $targetId = 1;
            $packageId = 2;
            $packageName = 'packageName';

            $fixture = [
                [$targetId, 'DOWNLOAD', 42],
                [$targetId, 'OTHER', $packageId],
                [42, 'DOWNLOAD', $packageId],
            ];
            DatabaseConnection::initializeTable('devices', ['hardware_id', 'name', 'ivalue'], $fixture);

            $package = new Package();
            $package->id = $packageId;

            $packageManager = $this->createMock(PackageManager::class);
            $packageManager->method('getPackage')->with($packageName)->willReturn($package);

            $assignments = $this->createAssignments(connection: $connection, packageManager: $packageManager);

            $target = new Client();
            $target->id = $targetId;

            try {
                $assignments->resetPackage($packageName, $target);
                $this->fail('Expected exception was not thrown');
            } catch (RuntimeException $exception) {
                $this->assertEquals('Package "packageName" is not assigned to client 1', $exception->getMessage());
                $this->assertEquals(
                    $fixture,
                    $connection->fetchAllNumeric(
                        'SELECT hardware_id, name, ivalue FROM devices ORDER BY hardware_id, name, ivalue'
                    )
                );
            }
        });
    }

    public function testResetPackageRollbackOnException()
    {
        DatabaseConnection::with(function (Connection $connection) {
            $targetId = 1;
            $packageId = 2;
            $packageName = 'packageName';

            $fixture = [[$targetId, 'DOWNLOAD', $packageId]];
            DatabaseConnection::initializeTable('devices', ['hardware_id', 'name', 'ivalue'], $fixture);

            $connectionProxy = Mockery::mock(Connection::class);
            $connectionProxy
                ->shouldReceive('beginTransaction')
                ->once()
                ->andReturnUsing($connection->beginTransaction(...));
            $connectionProxy->shouldReceive('createQueryBuilder')->andReturnUsing($connection->createQueryBuilder(...));
            $connectionProxy->shouldReceive('insert')->once()->andReturnUsing($connection->insert(...));
            $connectionProxy->shouldReceive('rollBack')->once()->andReturnUsing($connection->rollBack(...));

            $expectedException = new LogicException();
            $connectionProxy->shouldReceive('update')->andThrow($expectedException);

            $package = new Package();
            $package->id = $packageId;

            $packageManager = $this->createMock(PackageManager::class);
            $packageManager->method('getPackage')->with($packageName)->willReturn($package);

            $target = new Client();
            $target->id = $targetId;

            try {
                $assignments = $this->createAssignments(connection: $connectionProxy, packageManager: $packageManager);
                $assignments->resetPackage($packageName, $target);
                $this->fail('Expected exception was not thrown.');
            } catch (LogicException $exception) {
                $this->assertSame($expectedException, $exception);
                $this->assertEquals(
                    $fixture,
                    $connection->fetchAllNumeric('SELECT hardware_id, name, ivalue FROM devices')
                );
            }
        });
    }
}
