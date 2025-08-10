<?php

declare(strict_types=1);

namespace Braintacle\Database;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * Convenience base class for migrations.
 */
abstract class Migration extends AbstractMigration
{
    protected const TemplateTable = "Create table '%s'";
    protected const TemplateView = "Create view '%s'";

    protected const EngineInnoDb = 'InnoDB';

    #[Override]
    public function down(Schema $schema): void {}

    /**
     * Check for table presence and log message if it exists.
     */
    protected function tableExists(string $name): bool
    {
        $exists = $this->sm->tableExists($name);
        if ($exists) {
            $this->write('Table exists: ' . $name);
        }

        return $exists;
    }

    /**
     * Check for view presence and log message if it exists.
     */
    protected function viewExists(string $name): bool
    {
        foreach ($this->sm->listViews() as $view) {
            if ($view->getName() == $name) {
                $this->write('View exists: ' . $name);
                return true;
            }
        }

        return false;
    }

    /**
     * Create table with standard options.
     */
    protected function createTable(
        Schema $schema,
        string $name,
        string $comment,
        string $engine = self::EngineInnoDb,
    ): Table {
        return $schema->createTable($name)->addOption('engine', $engine)->setComment($comment);
    }

    /**
     * @param string[] $columnNames
     */
    protected function setPrimaryKey(Table $table, array $columnNames): void
    {
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames(...$columnNames)->create()
        );
    }
}
