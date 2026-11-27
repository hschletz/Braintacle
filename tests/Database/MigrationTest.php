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
                TestCase::assertEquals('Skipping because table exists: tableName', $message);
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
                TestCase::assertEquals('Skipping because view exists: view2', $message);
            }
        };

        $schema = $this->createStub(Schema::class);
        $migration->up($schema);
    }

    public function testCreateForeignKeyOnClient()
    {
        $connection = $this->createStub(Connection::class);
        $logger = $this->createStub(LoggerInterface::class);
        $migration = new class($connection, $logger) extends Migration
        {
            #[Override]
            public function up(Schema $schema): void
            {
                $constraint = $this->createForeignKeyOnClient('constraint_name');

                TestCase::assertEquals('constraint_name', $constraint->getObjectName()->toString());
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

        $schema = Schema::editor()->create();
        $migration->up($schema);
    }
}
