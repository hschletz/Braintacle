<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table as TableName;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250308180102 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, TableName::Packages);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(TableName::Packages)) {
            return;
        }

        $tableEditor = Table::editor()
            ->setUnquotedName(TableName::Packages)
            ->setOptions(['engine' => self::EngineInnoDb])
            ->setComment('Packages');

        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('fileid')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->setComment('Unix timestamp of package creation, also used as PK')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('name')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('priority')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('fragments')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('size')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('osname')
                ->setTypeName(Types::STRING)
                ->setLength(7)
                ->setNotNull(true)
                ->setComment('WINDOWS|LINUX|MacOSX')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('comment')
                ->setTypeName(Types::TEXT)
                ->setNotNull(false)
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('fileid')->create()
        );

        $tableEditor->addUniqueConstraint(
            UniqueConstraint::editor()
                ->setUnquotedColumnNames('name')
                ->setUnquotedName('download_available_name_unique_idx')
                ->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
