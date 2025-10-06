<?php

namespace Braintacle;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Time related functions.
 *
 * These methods are thin wrappers around various time functions. The
 * implementation in a class makes them easy to mock, simplifying tests.
 */
class Time implements ClockInterface
{
    public function __construct(private Connection $connection) {}

    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    /**
     * Get current time from the database.
     *
     * Current time is queried from the database, which may differ from the
     * local clock for various reasons:
     *
     * - The database may run on another server and the clocks are not in sync.
     * - The database and/or connection may use a different time zone than the
     *   local PHP configuration.
     *
     * These factors cannot be fully determined at runtime. By using database
     * time as a single reference for subsequent operations, time interval
     * calculations and comparisons become consistent across all operations
     * (including Braintacle server).
     *
     * The returned timestamp may differ from local time (due to the unknown
     * factors mentioned above) and must not be compared or otherwise mixed with
     * local time.
     */
    public function getDatabaseTime(): DateTimeInterface
    {
        // Fetch the current time in the database in the database's timezone
        $timestamp = $this->connection->fetchOne('SELECT CURRENT_TIMESTAMP');

        // Parse the timestamp with the database platform's format
        return Type::getType(Types::DATETIME_IMMUTABLE)->convertToPHPValue(
            $timestamp,
            $this->connection->getDatabasePlatform(),
        );
    }
}
