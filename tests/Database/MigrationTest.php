<?php

namespace Braintacle\Test\Database;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Types;
use Override;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class MigrationTest extends TestCase
{
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
        $view1 = View::editor()->setUnquotedName('view1')->setSQL('')->create();
        $view2 = View::editor()->setUnquotedName('view2')->setSQL('')->create();

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('introspectViews')->willReturn([$view1, $view2]);

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

    public function testCreateView()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                $view = $this->createView('viewName', 'SQL');

                TestCase::assertEquals('viewName', $view->getObjectName()->toString());
                TestCase::assertEquals('SQL', $view->getSql());
            }
        };

        $schema = new Schema();
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

                TestCase::assertEquals('tableName', $table->getObjectName()->toString());
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

                TestCase::assertEquals('tableName', $table->getObjectName()->toString());
                TestCase::assertEquals('a comment', $table->getComment());
                TestCase::assertEquals('otherEngine', $table->getOption('engine'));
            }
        };

        $schema = new Schema();
        $migration->up($schema);
    }

    public function testSetPrimaryKey()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);

        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                $table = $schema->createTable('table_name');
                $table->addColumn('col1', Types::INTEGER);
                $table->addColumn('col2', Types::INTEGER);

                $this->setPrimaryKey($table, ['col1', 'col2']);

                $pk = $table->getPrimaryKeyConstraint();
                TestCase::assertEquals(
                    ['col1', 'col2'],
                    array_map(
                        fn(UnqualifiedName $name) => $name->toString(),
                        $pk->getColumnNames()
                    )
                );
            }
        };

        $schema = new Schema();
        $migration->up($schema);
    }

    public function testAddClientForeignKey()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);
        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                $table = $schema->createTable('table_name');
                $table->addColumn('hardware_id', Types::INTEGER);

                $this->addClientForeignKey($table, 'constraint_name');

                $constraint = $table->getForeignKey('constraint_name');
                TestCase::assertEquals('hardware', $constraint->getReferencedTableName()->toString());
                TestCase::assertEquals(
                    ['id'],
                    array_map(
                        fn(UnqualifiedName $name) => $name->toString(),
                        $constraint->getReferencedColumnNames(),
                    )
                );
                TestCase::assertEquals(
                    ['hardware_id'],
                    array_map(
                        fn(UnqualifiedName $name) => $name->toString(),
                        $constraint->getReferencingColumnNames(),
                    )
                );
                TestCase::assertEquals(ReferentialAction::CASCADE, $constraint->getOnDeleteAction());
            }
        };

        $schema = new Schema();
        $migration->up($schema);
    }
}
