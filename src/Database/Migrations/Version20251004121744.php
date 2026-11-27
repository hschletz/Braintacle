<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table as TableName;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20251004121744 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, TableName::Locks);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(TableName::Locks)) {
            return;
        }

        $tableEditor = Table::editor()
            ->setUnquotedName('locks')
            ->setOptions(['engine' => self::EngineMemory])
            ->setComment('Advisory locks for clients and groups');

        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('hardware_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->setComment('Client or group ID')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(false)
                ->setComment('obsolete')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('since')
                ->setTypeName(Types::DATETIME_IMMUTABLE)
                ->setNotNull(true)
                ->setComment('Timestamp of lock creation')
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id')->create()
        );

        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('since')->setUnquotedName('locks_since_idx')->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
