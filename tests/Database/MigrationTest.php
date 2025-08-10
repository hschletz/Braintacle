<?php

namespace Braintacle\Test\Database;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Override;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class MigrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[DoesNotPerformAssertions]
    public function testDown()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void {}
        };

        $schema = $this->createStub(Schema::class);
        $migration->down($schema); // Overridden implementation will not throw exception
    }

    public function testTableExistsFalse()
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tableExists')->with('tableName')->willReturn(false);

        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                TestCase::assertFalse($this->tableExists('tableName'));
            }

            #[Override]
            public function write(string $message): void
            {
                TestCase::fail(__FUNCTION__ . '() should not have been called');
            }
        };

        $schema = $this->createStub(Schema::class);
        $migration->up($schema);
    }

    public function testTableExistsTrue()
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tableExists')->with('tableName')->willReturn(true);

        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                TestCase::assertTrue($this->tableExists('tableName'));
            }

            #[Override]
            public function write(string $message): void
            {
                TestCase::assertEquals('Table exists: tableName', $message);
            }
        };

        $schema = $this->createStub(Schema::class);
        $migration->up($schema);
    }

    public function testViewExistsFalse()
    {
        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $schemaManager->method('listViews')->willReturn([]);

        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                TestCase::assertFalse($this->viewExists('viewName'));
            }

            #[Override]
            public function write(string $message): void
            {
                TestCase::fail(__FUNCTION__ . '() should not have been called');
            }
        };

        $schema = $this->createStub(Schema::class);
        $migration->up($schema);
    }

    public function testViewExistsTrue()
    {
        $view1 = new View('view1', '');
        $view2 = new View('view2', '');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listViews')->willReturn([$view1, $view2]);

        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                TestCase::assertTrue($this->viewExists('view2'));
            }

            #[Override]
            public function write(string $message): void
            {
                TestCase::assertEquals('View exists: view2', $message);
            }
        };

        $schema = $this->createStub(Schema::class);
        $migration->up($schema);
    }

    public function testCreateTableDefaultEngine()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                $table = $this->createTable($schema, 'tableName', 'a comment');

                TestCase::assertEquals('tableName', $table->getName());
                TestCase::assertEquals('a comment', $table->getComment());
                TestCase::assertEquals('InnoDB', $table->getOption('engine'));
            }
        };

        $schema = new Schema();
        $migration->up($schema);
    }

    public function testCreateTableExplicitEngine()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                $table = $this->createTable($schema, 'tableName', 'a comment', 'otherEngine');

                TestCase::assertEquals('tableName', $table->getName());
                TestCase::assertEquals('a comment', $table->getComment());
                TestCase::assertEquals('otherEngine', $table->getOption('engine'));
            }
        };

        $schema = new Schema();
        $migration->up($schema);
    }

    #[DoesNotPerformAssertions]
    public function testSetPrimaryKey()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                $table = Mockery::mock(Table::class);
                $table->shouldReceive('addPrimaryKeyConstraint')->withArgs(function (PrimaryKeyConstraint $pk) {
                    $columns = array_map(fn(UnqualifiedName $name) => $name->toString(), $pk->getColumnNames());

                    return $columns == ['col1', 'col2'];
                });

                $this->setPrimaryKey($table, ['col1', 'col2']);
            }
        };

        $schema = new Schema();
        $migration->up($schema);
    }
}
