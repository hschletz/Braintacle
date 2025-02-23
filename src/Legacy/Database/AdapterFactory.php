<?php

namespace Braintacle\Legacy\Database;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo as PdoDriver;
use PDO;

/**
 * Wrap database connection in a Laminas-Db adapter.
 */
class AdapterFactory
{
    public function __construct(private DoctrineConnection $connection) {}

    // Return type is Adapter, not AdapterInterface, which is unsuitable because
    // it lacks commonly used methods like query(), which are only defined in
    // the Adapter class itself.
    public function __invoke(): Adapter
    {
        /** @var PDO */
        $nativeConnection = $this->connection->getNativeConnection();
        $driver = new PdoDriver($nativeConnection);

        return new Adapter($driver);
    }
}
