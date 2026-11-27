<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table as TableName;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250310183100 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, TableName::PackageHistory);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(TableName::PackageHistory)) {
            return;
        }

        $tableEditor = Table::editor()
            ->setUnquotedName(TableName::PackageHistory)
            ->setOptions(['engine' => self::EngineInnoDb])
            ->setComment('Packages already downloaded by a client');

        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('hardware_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('pkg_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id', 'pkg_id')->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
