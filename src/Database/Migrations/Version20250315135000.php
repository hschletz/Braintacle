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
final class Version20250315135000 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, TableName::ClientSystemInfo);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(TableName::ClientSystemInfo)) {
            return;
        }

        $tableEditor = Table::editor()
            ->setUnquotedName(TableName::ClientSystemInfo)
            ->setOptions(['engine' => self::EngineInnoDb])
            ->setComment("A client's system information provided by BIOS/UEFI");

        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('hardware_id')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(true)
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('smanufacturer')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('System manufacturer name')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('smodel')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Product name')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('ssn')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Serial')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('assettag')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Asset tag')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('type')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Type (provided by manufacturer)')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('bmanufacturer')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('BIOS/UEFI manufacturer name')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('bversion')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('BIOS/UEFI version')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('bdate')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('BIOS/UEFI date (no guarantee on format)')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('mmanufacturer')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Mainboard manufacturer name')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('mmodel')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Mainboard product name')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('msn')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Mainboard serial number')
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id')->create()
        );

        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('ssn')->setUnquotedName('bios_ssn_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('assettag')->setUnquotedName('bios_assettag_idx')->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
