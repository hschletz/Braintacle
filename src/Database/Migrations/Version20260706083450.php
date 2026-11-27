<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table as TableName;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20260706083450 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf('Create/alter tables %s and %s', TableName::RegistryValueDefinitions, TableName::RegistryData);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->sm->tableExists(TableName::RegistryValueDefinitions)) {
            // "id" column is never referenced. Change PK to "name" which is
            // actually used to reference the table. The existing unique index
            // on "name" is redundant and will be dropped.
            $table = $this->sm->introspectTableByUnquotedName(TableName::RegistryValueDefinitions);
            $tableEditor = $table->edit();
            $tableEditor->dropIndex(UnqualifiedName::unquoted('regconfig_name_unique_idx'));
            $tableEditor->dropPrimaryKeyConstraint();
            $tableEditor->dropColumn(UnqualifiedName::unquoted('id'));
            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setColumnNames(UnqualifiedName::unquoted('name'))->create()
            );
            $tableDiff = $this->createComparator()->compareTables($table, $tableEditor->create());
            foreach ($this->platform->getAlterTableSQL($tableDiff) as $sql) {
                $this->addSql($sql);
            }
        } else {
            $tableEditor = Table::editor()
                ->setUnquotedName('regconfig')
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment('Definitions of registry values to scan');

            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('id')
                    ->setTypeName(Types::INTEGER)
                    ->setNotNull(true)
                    ->setAutoincrement(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('name')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(true)
                    ->setComment('Display name')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('regtree')
                    ->setTypeName(Types::INTEGER)
                    ->setNotNull(true)
                    ->setComment(
                        'Root key (' .
                            '0=HKEY_CLASSES_ROOT, ' .
                            '1=HKEY_CURRENT_USER, ' .
                            '2=HKEY_LOCAL_MACHINE, ' .
                            '3=HKEY_USERS, ' .
                            '4=HKEY_CURRENT_CONFIG, ' .
                            '5=HKEY_DYN_DATA)'
                    )
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('regkey')
                    ->setTypeName(Types::TEXT)
                    ->setNotNull(true)
                    ->setComment('Subkey path')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('regvalue')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(true)
                    ->setComment('Value')
                    ->create()
            );

            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create()
            );

            $tableEditor->addUniqueConstraint(
                UniqueConstraint::editor()
                    ->setUnquotedColumnNames('name')
                    ->setUnquotedName('regconfig_name_unique_idx')
                    ->create()
            );

            foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
                $this->addSql($sql);
            }
        }

        if ($this->tableExists('registry')) {
            $registryDataTable = $this->sm->introspectTableByUnquotedName(TableName::RegistryData);
        } else {
            $tableEditor = Table::editor()
                ->setUnquotedName(TableName::RegistryData)
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment('registry data scanned on client');

            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('id')
                    ->setTypeName(Types::INTEGER)
                    ->setNotNull(true)
                    ->setAutoincrement(true)
                    ->create()
            );
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
                    ->setLength(255)
                    ->setNotNull(true)
                    ->setComment('Name of the value definition')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('regvalue')
                    ->setTypeName(Types::TEXT)
                    ->setNotNull(false)
                    ->setComment('Data')
                    ->create()
            );

            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create()
            );

            $tableEditor->addIndex(
                Index::editor()
                    ->setUnquotedColumnNames('hardware_id')
                    ->setUnquotedName('registry_hardware_id_idx')
                    ->create()
            );
            $tableEditor->addIndex(
                Index::editor()->setUnquotedColumnNames('name')->setUnquotedName('registry_name_idx')->create()
            );

            $registryDataTable = $tableEditor->create();

            foreach ($this->platform->getCreateTableSQL($registryDataTable) as $sql) {
                $this->addSql($sql);
            }
        }

        // Foreign key constraints did not exist in previous schema. Add them unconditionally.
        $fkClient = $this->createForeignKeyOnClient('fk_registry_hardware_id');
        $fkDefinition = ForeignKeyConstraint::editor()
            ->setUnquotedName('fk_registry_regconfig_name')
            ->setReferencedTableName(OptionallyQualifiedName::unquoted(TableName::RegistryValueDefinitions))
            ->setReferencedColumnNames(UnqualifiedName::unquoted('name'))
            ->setReferencingColumnNames(UnqualifiedName::unquoted('name'))
            ->setOnDeleteAction(ReferentialAction::CASCADE)
            ->create();
        $registryDataTableEditor = $registryDataTable
            ->edit()
            ->addForeignKeyConstraint($fkClient)
            ->addForeignKeyConstraint($fkDefinition);
        $tableDiff = $this->createComparator()->compareTables($registryDataTable, $registryDataTableEditor->create());

        foreach ($this->platform->getAlterTableSQL($tableDiff) as $sql) {
            $this->addSql($sql);
        }
    }
}
