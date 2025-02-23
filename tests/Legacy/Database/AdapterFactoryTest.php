<?php

namespace Braintacle\Test\Legacy\Database;

use Braintacle\Legacy\Database\AdapterFactory;
use Doctrine\DBAL\Connection;
use Laminas\Db\Adapter\Driver\Pdo\Pdo as PdoDriver;
use PDO as PdoConnection;
use PHPUnit\Framework\TestCase;

class AdapterFactoryTest extends TestCase
{
    public function testFactory()
    {
        $pdo = $this->createStub(PdoConnection::class);
        $pdo->method('getAttribute')->willReturn(''); // Prevent warning within Laminas driver

        $connection = $this->createStub(Connection::class);
        $connection->method('getNativeConnection')->willReturn($pdo);

        $factory = new AdapterFactory($connection);
        $adapter = $factory();
        $driver = $adapter->getDriver();

        $this->assertInstanceOf(PdoDriver::class, $driver);
        $this->assertSame($pdo, $driver->getConnection()->getResource());
    }
}
