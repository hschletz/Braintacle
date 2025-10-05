<?php

namespace Braintacle;

use Braintacle\Database\Table;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Model\Client\Client;
use Model\Config;
use Model\Group\Group;
use Psr\Log\LoggerInterface;

/**
 * Advisory locks for clients and groups.
 *
 * Locks should be released as soon as possible. A try/finally block can prevent
 * stale locks in case of an error. If a lock does not get released, it will
 * expire after the configured timeout.
 *
 * Locks can be nested. If lock() is called more than once on the same object,
 * the lock is only released after a matching number of release() calls. This
 * does not refresh the expiry timeout - only the first lock() call sets the
 * timeout.
 *
 * The lock is implemented as a row in the "locks" table. This must be accounted
 * for when using transactions.
 */
final class Locks
{
    /**
     * @var array<int, int>
     */
    private array $nestCounts = [];

    /**
     * @var array<int, DateTimeImmutable>
     */
    private array $timeouts = [];

    public function __construct(
        private Connection $connection,
        private Config $config,
        private Time $time,
        private LoggerInterface $logger,
    ) {}

    /**
     * Check whether the given object is locked.
     */
    public function isLocked(Client | Group $object): bool
    {
        return (bool) ($this->nestCounts[$object->id] ?? false);
    }

    /**
     * Lock object (prevent altering by server or another console user).
     *
     * @return bool TRUE if the object could be locked (i.e. not already locked
     * by another process)
     */
    public function lock(Client | Group $object): bool
    {
        $id = $object->id;
        if ($this->isLocked($object)) {
            $this->nestCounts[$id]++;

            return true;
        }

        $now = $this->time->getDatabaseTime();
        $expireInterval = new DateInterval("PT{$this->config->lockValidity}S");

        // Check if a lock already exists.
        $lockedAt = $this->connection
            ->createQueryBuilder()
            ->select('since')
            ->from(Table::Locks)
            ->where('hardware_id = :id')
            ->setParameter('id', $id)
            ->fetchOne();
        if ($lockedAt) {
            // A lock exists. Check its timestamp. Actual timezone does not
            // matter as long as it is the same as $now.
            $expire = (new DateTimeImmutable($lockedAt, $now->getTimezone()))->add($expireInterval);
            if ($now > $expire) {
                // The existing lock is stale and can be reused.
                $this->connection->update(
                    Table::Locks,
                    ['since' => $now],
                    ['hardware_id' => $id],
                    ['since' => Types::DATETIME_IMMUTABLE],
                );
                $success = true;
            } else {
                // The existing lock is still valid. The object can not be
                // locked at this time.
                $success = false;
            }
        } else {
            // No lock present yet. Create one. Another process might have
            // created one in the meantime, causing the insertion to fail. In
            // that case, the database exception is silently caught and the lock
            // does not get created.
            try {
                $this->connection->insert(
                    Table::Locks,
                    ['hardware_id' => $id, 'since' => $now],
                    ['since' => Types::DATETIME_IMMUTABLE],
                );
                $success = true;
            } catch (UniqueConstraintViolationException) {
                $success = false;
            }
        }

        if ($success) {
            // Keep track of the locks.
            $this->timeouts[$id] = DateTimeImmutable::createFromInterface($now)->add($expireInterval);
            $this->nestCounts[$id] = ($this->nestCounts[$id] ?? 0) + 1;
        }

        return $success;
    }

    /**
     * Release lock on an object.
     *
     * Only locks created by the current instance can be released.
     */
    public function release(Client | Group $object): void
    {
        if (!$this->isLocked($object)) {
            return;
        }

        $id = $object->id;
        if (($this->nestCounts[$id] ?? 0) > 1) {
            $this->nestCounts[$id]--;

            return;
        }

        $now = $this->time->getDatabaseTime();
        $isExpired = ($now > $this->timeouts[$id]);
        unset($this->timeouts[$id]);
        unset($this->nestCounts[$id]);
        if ($isExpired) {
            // This instance's lock has expired. The database entry may no
            // longer belong to this instance. It will be deleted or reused by
            // a different instance.
            // This should never happen unless the lock validity time is too
            // short. Generate a warning about the misconfiguration.
            $this->logger->warning('Lock expired prematurely. Increase lock lifetime.');
        } else {
            $this->connection->delete(Table::Locks, ['hardware_id' => $id]);
        }
    }
}
