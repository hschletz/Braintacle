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
final class Version20250923180955 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, TableName::GroupInfo);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(TableName::GroupInfo)) {
            return;
        }


        $tableEditor = Table::editor()
            ->setUnquotedName('groups')
            ->setOptions(['engine' => self::EngineInnoDb])
            ->setComment("Extra information on groups, supplementing the information in the 'hardware' table");

        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('hardware_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('request')
                ->setTypeName(Types::TEXT)
                ->setNotNull(false)
                ->setComment('An SQL query that delivers client IDs for dynamic membership')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('xmldef')
                ->setTypeName(Types::TEXT)
                ->setNotNull(false)
                ->setComment('Pseudo-XML for multiple SQL queries')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('create_time')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->setDefaultValue(0)
                ->setComment('UNIX timestamp of last cache computation')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('revalidate_from')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->setDefaultValue(0)
                ->setComment('create_time + random offset, used to determine time for next cache computation')
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id')->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
