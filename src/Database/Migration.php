<?php

declare(strict_types=1);

namespace Braintacle\Database;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ComparatorConfig;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\Name\UnquotedIdentifierFolding;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * Convenience base class for migrations.
 */
abstract class Migration extends AbstractMigration
{
    protected const TemplateTable = "Create table '%s'";
    protected const TemplateTableDrop = "Drop table '%s'";
    protected const TemplateView = "Create view '%s'";

    protected const EngineInnoDb = 'InnoDB';
    protected const EngineMemory = 'MEMORY';

    #[Override]
    public function down(Schema $schema): void {}

    /**
     * Check for table presence and log message if it exists.
     */
    protected function tableExists(string $name): bool
    {
        $exists = $this->sm->tableExists($name);
        if ($exists) {
            $this->write('Skipping because table exists: ' . $name);
        }

        return $exists;
    }

    /**
     * Check for view presence and log message if it exists.
     */
    protected function viewExists(string $name): bool
    {
        foreach ($this->sm->introspectViews() as $view) {
            $normalizedName = $view
                ->getObjectName()
                ->getUnqualifiedName()
                ->toNormalizedValue(UnquotedIdentifierFolding::LOWER);
            if ($normalizedName == strtolower($name)) {
                $this->write('Skipping because view exists: ' . $name);
                return true;
            }
        }

        return false;
    }

    /**
     * Create a foreign key constraint to the 'hardware' table refererenced by 'hardware_id'
     */
    protected function createForeignKeyOnClient(string $constraintName): ForeignKeyConstraint
    {
        return ForeignKeyConstraint::editor()
            ->setUnquotedName($constraintName)
            ->setReferencedTableName(OptionallyQualifiedName::unquoted('hardware'))
            ->setReferencedColumnNames(UnqualifiedName::unquoted('id'))
            ->setReferencingColumnNames(UnqualifiedName::unquoted('hardware_id'))
            ->setOnDeleteAction(ReferentialAction::CASCADE)
            ->create();
    }

    /**
     * Create Comparator with deprecated features disabled.
     *
     * @codeCoverageIgnore
     */
    protected function createComparator(): Comparator
    {
        return $this->sm->createComparator(new ComparatorConfig(reportModifiedIndexes: false));
    }
}
