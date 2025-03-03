<?php

namespace Braintacle\Test\Package;

use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Package\Assignments;
use Braintacle\Test\DatabaseConnection;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use Model\Client\Client;
use Model\Package\Assignment;
use Model\Package\Package;
use Model\Package\PackageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

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
                [$targetId, 'DOWNLOAD_suffix', $packageId],
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
}
