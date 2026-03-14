<?php

namespace Braintacle\Package;

use Braintacle\Database\Table;
use Braintacle\Group\Group;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Package\Assignment;
use Model\Package\PackageManager;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Throwable;

final class Assignments
{
    private const Target = 'hardware_id';
    private const Action = 'name';
    private const PackageId = 'ivalue';
    private const Status = 'tvalue';
    private const Timestamp = 'comments';
    private const PackageKey = 'fileid';
    private const PackageName = 'name';
    private const HistoryId = 'pkg_id';

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
    public function getAssignedPackages(Client|Group $target): iterable
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $select = $queryBuilder
            ->select('p.' . self::PackageName, self::Status, self::Timestamp)
            ->from(Table::PackageAssignments, 'a')
            ->innerJoin('a', Table::Packages, 'p', $expr->eq('p.' . self::PackageKey, 'a.' . self::PackageId))
            ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
            ->andWhere($expr->eq('a.' . self::Action, $queryBuilder->createPositionalParameter(self::ActionDownload)))
            ->orderBy('p.' . self::PackageName);

        return $this->dataProcessor->iterate($select->executeQuery()->iterateAssociative(), Assignment::class);
    }

    /**
     * Get a list of packages assignable to a client or group.
     *
     * A package is assignable if it is not already assigned and not listed in a
     * client's history. The latter is always the case for groups.
     *
     * @return iterable<string>
     */
    public function getAssignablePackages(Client|Group $target): iterable
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $select = $queryBuilder
            ->select('p.' . self::PackageName)
            ->from(Table::Packages, 'p')
            ->leftJoin( // assigned packages
                'p',
                Table::PackageAssignments,
                'a',
                $expr->and(
                    $expr->eq(self::PackageId, self::PackageKey),
                    $expr->eq('a.' . self::Target, $queryBuilder->createPositionalParameter($target->id)),
                    $expr->eq('a.' . self::Action, $queryBuilder->createPositionalParameter(self::ActionDownload))
                ),
            )
            ->leftJoin( // packages from history
                'p',
                Table::PackageHistory,
                'h',
                $expr->and(
                    $expr->eq(self::HistoryId, self::PackageKey),
                    $expr->eq('h.' . self::Target, $queryBuilder->createPositionalParameter($target->id)),
                ),
            )
            // Select only rows not containing data from joined tables.
            ->where($expr->isNull(self::PackageId))
            ->andWhere($expr->isNull(self::HistoryId))
            ->orderBy('p.' . self::PackageName);

        return $select->executeQuery()->iterateColumn();
    }

    public function assignPackage(string $packageName, Client|Group $target): void
    {
        $package = $this->packageManager->getPackage($packageName);
        $this->connection->insert(Table::PackageAssignments, [
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
            ->delete(Table::PackageAssignments)
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
                ->from(Table::PackageAssignments)
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
                ->from(Table::PackageAssignments)
                ->where($expr->eq(self::Target, $queryBuilder->createPositionalParameter($target->id)))
                ->andWhere($expr->eq(self::PackageId, $queryBuilder->createPositionalParameter($package->id)))
                ->andWhere($expr->eq(self::Action, $queryBuilder->createPositionalParameter(self::ActionReset)));
            if ($select->fetchOne() != 1) {
                $this->connection->insert(Table::PackageAssignments, [
                    self::Target => $target->id,
                    self::Action => self::ActionReset,
                    self::PackageId => $package->id,
                    self::Status => '1',
                ]);
            }

            // Reset assignment row
            $this->connection->update(
                Table::PackageAssignments,
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

    /**
     * Update package assignments.
     *
     * Sets a new package on existing assignments. Updated assignments have
     * their status reset to "pending" and their options (force, schedule,
     * post cmd) removed.
     */
    public function updateAssignments(
        int $oldPackageId,
        int $newPackageId,
        bool $deployPending,
        bool $deployRunning,
        bool $deploySuccess,
        bool $deployError,
        bool $deployGroups
    ): void {
        if (!($deployPending || $deployRunning || $deploySuccess || $deployError || $deployGroups)) {
            return; // nothing to do
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $where = CompositeExpression::and(
            $expr->eq(self::PackageId, ':oldId'),
            $expr->eq(self::Action, ':download'),
        );
        $params = [
            'oldId' => $oldPackageId,
            'newId' => $newPackageId,
            'download' => self::ActionDownload,
            'downloadSwitch' => 'DOWNLOAD_SWITCH',
            'downloadPattern' => 'DOWNLOAD_%',
            'timestamp' => $this->clock->now()->format(Assignment::DATEFORMAT),
            'pending' => Assignment::PENDING,
        ];

        // Additional filters are only necessary if not all conditions are set
        if (!($deployPending && $deployRunning && $deploySuccess && $deployError && $deployGroups)) {
            $groups = $this->connection->createQueryBuilder()->select('hardware_id')->from(Table::GroupInfo);
            $filters = [];
            if ($deployPending) {
                $filters[] = CompositeExpression::and(
                    $expr->isNull(self::Status),
                    $expr->notIn(self::Target, $groups),
                );
            }
            if ($deployRunning) {
                $filters[] = $expr->eq(self::Status, ':running');
                $params['running'] = Assignment::RUNNING;
            }
            if ($deploySuccess) {
                $filters[] = $expr->eq(self::Status, ':success');
                $params['success'] = Assignment::SUCCESS;
            }
            if ($deployError) {
                $filters[] = $expr->like(self::Status, ':error');
                $params['error'] = Assignment::ERROR_PREFIX . '%';
            }
            if ($deployGroups) {
                $filters[] = $expr->in(self::Target, $groups);
            }
            // @phpstan-ignore arguments.count (initial condition guarantees at least 1 argument)
            $where = $where->with(CompositeExpression::or(...$filters));
        }

        // Remove DOWNLOAD_* options from updated assignments
        $subquery = $this->connection->createQueryBuilder()
            ->select(self::Target)
            ->from(Table::ClientConfig)
            ->where($where);
        $this->connection->createQueryBuilder()
            ->delete(Table::ClientConfig)
            ->where(
                $expr->eq(self::PackageId, ':oldId'),
                $expr->neq(self::Action, ':downloadSwitch'),
                $expr->like(self::Action, ':downloadPattern'),
                $expr->in(self::Target, $subquery),
            )
            ->setParameters($params)
            ->executeStatement();

        // Update package ID and reset status
        $this->connection->createQueryBuilder()
            ->update(Table::ClientConfig)
            ->set(self::PackageId, ':newId')
            ->set(self::Status, ':pending')
            ->set(self::Timestamp, ':timestamp')
            ->where($where)
            ->setParameters($params)
            ->executeStatement();
    }
}
