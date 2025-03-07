<?php

namespace Braintacle\Test;

use Braintacle\Database\ConnectionFactory;
use Braintacle\Database\Migrations;
use Doctrine\DBAL\Connection;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\NullOutput;
use Throwable;

/**
 * Test wrapper for database connection.
 */
final class DatabaseConnection
{
    private static Connection $connection;
    private static bool $wrapperStarted = false;

    private static function getConnection(): Connection
    {
        if (!isset(self::$connection)) {
            $dsn = getenv('BRAINTACLE_TEST_DATABASE') ?: 'pdo-sqlite:///:memory:';
            self::$connection = ConnectionFactory::createConnection($dsn);
            $migrations = new Migrations(self::$connection, new Application());
            $migrations->migrate(new NullOutput(), 'latest');
        }

        return self::$connection;
    }

    /**
     * Run callback with connection.
     *
     * The callback is run in a transaction that will be rolled back afterwards,
     * thus trying to make tests side-effect free and simplifying test
     * isolation. This should work with correct code, but cannot be guaranteed.
     * If tested code is faulty and commits more transactions than it starts,
     * changes will be permanent.
     *
     * Unbalanced transactions (i.e. a mismatch between beginTransaction() and
     * commit()/rollBack() calls) are detected and reported.
     *
     * Calls cannot be nested.
     *
     * @param callable(Connection):void $callback
     */
    public static function with(callable $callback): void
    {
        if (self::$wrapperStarted) {
            throw new LogicException(__FUNCTION__ . '() already started');
        }

        $connection = self::getConnection();
        $connection->beginTransaction();
        $initialNestingLevel = $connection->getTransactionNestingLevel();

        self::$wrapperStarted = true;
        $throwable = null;
        try {
            $callback($connection);
        } catch (Throwable $t) {
            $throwable = $t;
        }

        self::$wrapperStarted = false;
        $finalNestingLevel = $connection->getTransactionNestingLevel();

        // Roll back all transactions that have been started since invocation of
        // this method, even when the callback forgot to end a transaction.
        while ($connection->getTransactionNestingLevel() >= $initialNestingLevel) {
            $connection->rollBack();
        }

        if ($finalNestingLevel != $initialNestingLevel) {
            throw new RuntimeException(
                message: 'Incorrect transaction nesting level - forgotten or superfluous commit()/rollBack()?',
                previous: $throwable,
            );
        }

        if ($throwable) {
            throw $throwable;
        }
    }

    /**
     * Initialize a database table.
     *
     * This can only be called from a callback passed to with().
     *
     * @param iterable<list> $rows
     */
    public static function initializeTable(string $table, array $columns, iterable $rows): void
    {
        if (!self::$wrapperStarted) {
            throw new LogicException(__FUNCTION__ . '() must be invoked from with() callback');
        }

        $connection = self::getConnection();
        $connection->executeStatement($connection->getDatabasePlatform()->getTruncateTableSQL($table));
        foreach ($rows as $row) {
            $connection->insert($table, array_combine($columns, $row));
        }
    }
}
