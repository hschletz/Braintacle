<?php

namespace Braintacle\Package;

use Doctrine\DBAL\Connection;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Group\Group;
use Model\Package\Assignment;
use Model\Package\PackageManager;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Throwable;

final class Assignments
{
    private const TableAssignments = 'devices';
    private const TablePackages = 'download_available';

    private const Target = 'hardware_id';
    private const Action = 'name';
    private const PackageId = 'ivalue';
    private const Status = 'tvalue';
    private const Timestamp = 'comments';
    private const PackageName = 'name';

    private const ActionDownload = 'DOWNLOAD';
    private const ActionReset = 'DOWNLOAD_FORCE';
    private const DeletePattern = "'DOWNLOAD%'";

    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
        private PackageManager $packageManager,
        private DataProcessor $dataProcessor,
    ) {}

    /**
     * @return iterable<Assignment>
     */
    public function get(Client $client): iterable
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $select = $queryBuilder
            ->select('p.' . self::PackageName, self::Status, self::Timestamp)
            ->from(self::TableAssignments, 'a')
            ->innerJoin('a', self::TablePackages, 'p', $expr->eq('p.fileid', 'a.' . self::PackageId))
            ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($client->id)))
            ->andWhere($expr->eq('a.' . self::Action, $queryBuilder->createPositionalParameter(self::ActionDownload)))
            ->orderBy('p.' . self::PackageName);

        return $this->dataProcessor->iterate($select->executeQuery()->iterateAssociative(), Assignment::class);
    }

    public function assignPackage(string $packageName, Client|Group $target): void
    {
        $package = $this->packageManager->getPackage($packageName);
        $this->connection->insert(self::TableAssignments, [
            self::Target => $target->id,
            self::Action => self::ActionDownload,
            self::PackageId => $package->id,
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
            ->delete(self::TableAssignments)
            ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
            ->andWhere($expr->eq(self::PackageId, $queryBuilder->createPositionalParameter($package->id)))
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
                ->from(self::TableAssignments)
                ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
                ->andWhere($expr->eq(self::PackageId, $queryBuilder->createPositionalParameter($package->id)))
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
                ->from(self::TableAssignments)
                ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
                ->andWhere($expr->eq(self::PackageId, $queryBuilder->createPositionalParameter($package->id)))
                ->andWhere($expr->eq(self::Action, $queryBuilder->createPositionalParameter(self::ActionReset)));
            if ($select->fetchOne() != 1) {
                $this->connection->insert(self::TableAssignments, [
                    self::Target => $target->id,
                    self::Action => self::ActionReset,
                    self::PackageId => $package->id,
                    self::Status => '1',
                ]);
            }

            // Reset assignment row
            $this->connection->update(
                self::TableAssignments,
                [
                    self::Status => Assignment::PENDING,
                    self::Timestamp => $this->clock->now()->format(Assignment::DATEFORMAT),
                ],
                [
                    self::Target => $target->id,
                    self::PackageId => $package->id,
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
