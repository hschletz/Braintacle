<?php

namespace Braintacle\Database;

use Braintacle\AppConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

/** @codeCoverageIgnore */
class ConnectionFactory
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private AppConfig $appConfig) {}

    public function __invoke(): Connection
    {
        return static::createConnection($this->appConfig->database['dsn']);
    }

    public static function createConnection(string $dsn): Connection
    {
        $parser = new DsnParser();
        $params = $parser->parse($dsn);
        if (str_contains($params['driver'], 'mysql')) {
            $params['charset'] = 'utf8mb4';
        } else {
            $params['charset'] = 'utf8';
        }
        return DriverManager::getConnection($params);
    }
}
