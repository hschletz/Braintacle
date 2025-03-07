<?php

namespace Braintacle\Package;

use Doctrine\DBAL\Connection;
use Model\Client\Client;
use Model\Group\Group;
use Model\Package\Assignment;
use Model\Package\PackageManager;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Throwable;

final class Assignments
{
    private const Table = 'devices';

    private const Target = 'hardware_id';
    private const Action = 'name';
    private const Package = 'ivalue';
    private const Status = 'tvalue';
    private const Timestamp = 'comments';

    private const ActionDownload = 'DOWNLOAD';
    private const ActionReset = 'DOWNLOAD_FORCE';
    private const DeletePattern = "'DOWNLOAD%'";

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
            self::Action => self::ActionDownload,
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

    public function unassignPackage(string $packageName, Client|Group $target): void
    {
        $package = $this->packageManager->getPackage($packageName);
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->delete(self::Table)
            ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
            ->andWhere($expr->eq(self::Package, $queryBuilder->createPositionalParameter($package->id)))
            ->andWhere($expr->like(self::Action, self::DeletePattern));
        $queryBuilder->executeStatement();
    }

    /**
     * Reset status of a package to "pending".
     */
    public function resetPackage(string $packageName, Client $target): void
    {
        $package = $this->packageManager->getPackage($packageName);
        $this->connection->beginTransaction();
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $expr = $queryBuilder->expr();
            $select = $queryBuilder
                ->select('count(*)')
                ->from(self::Table)
                ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
                ->andWhere($expr->eq(self::Package, $queryBuilder->createPositionalParameter($package->id)))
                ->andWhere($expr->eq(self::Action, $queryBuilder->createPositionalParameter(self::ActionDownload)));
            if ($select->fetchOne() != 1) {
                throw new RuntimeException(
                    sprintf('Package "%s" is not assigned to client %d', $packageName, $target->id)
                );
            }

            // Create DOWNLOAD_FORCE row if it does not already exist. This row
            // is required for overriding the client's package history.
            $queryBuilder = $this->connection->createQueryBuilder();
            $expr = $queryBuilder->expr();
            $select = $queryBuilder
                ->select('count(*)')
                ->from(self::Table)
                ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
                ->andWhere($expr->eq(self::Package, $queryBuilder->createPositionalParameter($package->id)))
                ->andWhere($expr->eq(self::Action, $queryBuilder->createPositionalParameter(self::ActionReset)));
            if ($select->fetchOne() != 1) {
                $this->connection->insert(self::Table, [
                    self::Target => $target->id,
                    self::Action => self::ActionReset,
                    self::Package => $package->id,
                    self::Status => '1',
                ]);
            }

            // Reset assignment row
            $this->connection->update(
                self::Table,
                [
                    self::Status => Assignment::PENDING,
                    self::Timestamp => $this->clock->now()->format(Assignment::DATEFORMAT),
                ],
                [
                    self::Target => $target->id,
                    self::Package => $package->id,
                    self::Action => self::ActionDownload,
                ]
            );

            $this->connection->commit();
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }
}
