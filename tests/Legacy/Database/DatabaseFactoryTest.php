<?php

namespace Braintacle\Test\Legacy\Database;

use Braintacle\Legacy\Database\DatabaseFactory;
use Laminas\Db\Adapter\Adapter;
use Nada\Column\AbstractColumn as Column;
use Nada\Database\AbstractDatabase;
use Nada\Factory;
use PHPUnit\Framework\TestCase;

class DatabaseFactoryTest extends TestCase
{
    public function testPostgreSql()
    {
        $adapter = $this->createStub(Adapter::class);

        $database = $this->createMock(AbstractDatabase::class);
        $database->expects($this->once())->method('setTimezone')->with(null);
        $database->method('isPgsql')->willReturn(true);
        $database->method('isMysql')->willReturn(false);
        $database->method('isSqlite')->willReturn(false);

        $nadaFactory = $this->createMock(Factory::class);
        $nadaFactory->method('__invoke')->with($adapter)->willReturn($database);

        $databaseFactory = new DatabaseFactory($nadaFactory, $adapter);
        $this->assertInstanceOf(AbstractDatabase::class, $databaseFactory());
        $this->assertEquals([], $database->emulatedDatatypes);
    }

    public function testMySql()
    {
        $adapter = $this->createStub(Adapter::class);

        $database = $this->createMock(AbstractDatabase::class);
        $database->expects($this->once())->method('setTimezone')->with(null);
        $database->method('isPgsql')->willReturn(false);
        $database->method('isMysql')->willReturn(true);
        $database->method('isSqlite')->willReturn(false);

        $nadaFactory = $this->createMock(Factory::class);
        $nadaFactory->method('__invoke')->with($adapter)->willReturn($database);

        $databaseFactory = new DatabaseFactory($nadaFactory, $adapter);
        $this->assertInstanceOf(AbstractDatabase::class, $databaseFactory());
        $this->assertEquals([Column::TYPE_BOOL], $database->emulatedDatatypes);
    }

    public function testSqlite()
    {
        $adapter = $this->createStub(Adapter::class);

        $database = $this->createMock(AbstractDatabase::class);
        $database->expects($this->once())->method('setTimezone')->with(null);
        $database->method('isPgsql')->willReturn(false);
        $database->method('isMysql')->willReturn(false);
        $database->method('isSqlite')->willReturn(true);

        $nadaFactory = $this->createMock(Factory::class);
        $nadaFactory->method('__invoke')->with($adapter)->willReturn($database);

        $databaseFactory = new DatabaseFactory($nadaFactory, $adapter);
        $this->assertInstanceOf(AbstractDatabase::class, $databaseFactory());
        $this->assertEquals(
            [Column::TYPE_BOOL, Column::TYPE_DATE, Column::TYPE_DECIMAL, Column::TYPE_TIMESTAMP],
            $database->emulatedDatatypes,
        );
    }
}
