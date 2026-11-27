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
final class Version20250315134219 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, TableName::GroupMemberships);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(TableName::GroupMemberships)) {
            return;
        }

        $tableEditor = Table::editor()
            ->setUnquotedName(TableName::GroupMemberships)
            ->setOptions(['engine' => self::EngineInnoDb])
            ->setComment('Group memberships');

        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('hardware_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('group_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('static')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(false)
                ->setDefaultValue(0)
                ->setComment('Membership type: 0=automatic (cached), 1=manual, 2=excluded')
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id', 'group_id')->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
