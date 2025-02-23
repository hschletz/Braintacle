<?php

namespace Braintacle\Test\Package;

use Braintacle\Package\Assignments;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use Model\Client\Client;
use Model\Package\Assignment;
use Model\Package\Package;
use Model\Package\PackageManager;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

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
}
