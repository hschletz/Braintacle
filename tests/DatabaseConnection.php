<?php

namespace Braintacle\Test;

use Braintacle\Database\ConnectionFactory;
use Braintacle\Database\Migrations;
use Doctrine\DBAL\Connection;
use LogicException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\NullOutput;

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
     * The callback will be run in a transaction that will be rolled back
     * afterwards, thus making tests side-effect free and simplifying test
     * isolation.
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
        self::$wrapperStarted = true;
        try {
            $callback($connection);
        } finally {
            self::$wrapperStarted = false;
            $connection->rollBack();
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
        foreach ($rows as $row) {
            $connection->insert($table, array_combine($columns, $row));
        }
    }
}
