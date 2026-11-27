<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250301120607 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(static::TemplateTable, 'devices');
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists('devices')) {
            return;
        }

        $tableEditor = Table::editor()
            ->setUnquotedName('devices')
            ->setOptions(['engine' => self::EngineInnoDb])
            ->setComment('Configuration for specific clients and groups');

        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('hardware_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('name')
                ->setTypeName(Types::STRING)
                ->setLength(50)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('ivalue')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('tvalue')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('comments')
                ->setTypeName(Types::TEXT)
                ->setNotNull(false)
                ->setComment("Timestamp of last package status change in perl's localtime() format")
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id', 'name', 'ivalue')->create()
        );

        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('hardware_id')->setUnquotedName('devices_hardware_id_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('name')->setUnquotedName('devices_name_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('ivalue')->setUnquotedName('devices_ivalue_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('tvalue')->setUnquotedName('devices_tvalue_idx')->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
