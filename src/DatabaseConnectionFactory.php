<?php

namespace Braintacle;

use Braintacle\AppConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

/** @codeCoverageIgnore */
class DatabaseConnectionFactory
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private AppConfig $appConfig) {}

    public function __invoke(): Connection
    {
        return static::createConnection($this->appConfig->database);
    }

    public static function createConnection(array $config): Connection
    {
        $parser = new DsnParser();
        $dsn = $parser->parse($config['dsn']);
        if (str_contains($dsn['driver'], 'mysql')) {
            $dsn['charset'] = 'utf8mb4';
        } else {
            $dsn['charset'] = 'utf8';
        }
        return DriverManager::getConnection($dsn);
    }
}
