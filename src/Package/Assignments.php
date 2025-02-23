<?php

namespace Braintacle\Package;

use Doctrine\DBAL\Connection;
use Model\Client\Client;
use Model\Group\Group;
use Model\Package\Assignment;
use Model\Package\PackageManager;
use Psr\Clock\ClockInterface;

final class Assignments
{
    private const Table = 'devices';

    private const Target = 'hardware_id';
    private const Action = 'name';
    private const Package = 'ivalue';
    private const Status = 'tvalue';
    private const Timestamp = 'comments';

    private const ActionValue = 'DOWNLOAD';

    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
        private PackageManager $packageManager
    ) {}

    public function assignPackage(string $packageName, Client|Group $target): void
    {
        $package = $this->packageManager->getPackage($packageName);
        $this->connection->insert(self::Table, [
            self::Target => $target->id,
            self::Action => self::ActionValue,
            self::Package => $package->id,
            self::Status => Assignment::PENDING,
            self::Timestamp => $this->clock->now()->format(Assignment::DATEFORMAT),
        ]);
    }

    /**
     * @param string[] $packages
     */
    public function assignPackages(array $packages, Client|Group $target): void
    {
        foreach ($packages as $package) {
            $this->assignPackage($package, $target);
        }
    }
}
