<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20260706083450 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf('Create/alter tables %s and %s', Table::RegistryValueDefinitions, Table::RegistryData);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(Table::RegistryValueDefinitions)) {
            // "id" column is never referenced. Change PK to "name" which is
            // actually used to reference the table. The existing unique index
            // on "name" is redundant and will be dropped.
            $table = $schema->getTable(Table::RegistryValueDefinitions);
            $table->dropIndex('regconfig_name_unique_idx');
            $table->dropPrimaryKey();
            $table->dropColumn('id');
            $this->setPrimaryKey($table, ['name']);
        } else {
            $table = $this->createTable(
                $schema,
                Table::RegistryValueDefinitions,
                'Definitions of registry values to scan',
            );

            $table->addColumn('name', Types::STRING)->setLength(255)->setNotnull(true)->setComment('Display name');
            $table->addColumn('regtree', Types::INTEGER)->setNotnull(true)->setComment(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Root key (0=HKEY_CLASSES_ROOT, 1=HKEY_CURRENT_USER, 2=HKEY_LOCAL_MACHINE, 3=HKEY_USERS, 4=HKEY_CURRENT_CONFIG)'
            );
            $table->addColumn('regkey', Types::TEXT)->setNotnull(true)->setComment('Subkey path');
            $table->addColumn('regvalue', Types::STRING)->setLength(255)->setNotnull(true)->setComment('Value');

            $this->setPrimaryKey($table, ['name']);
        }

        $comment = 'Data';
        if ($this->tableExists('registry')) {
            // Comment in previous schema was outdated.
            $schema->getTable(Table::RegistryData)->setComment($comment);
        } else {
            $table = $this->createTable($schema, Table::RegistryData, 'Registry data scanned on client');

            $table->addColumn('id', Types::INTEGER)->setNotnull(true)->setAutoincrement(true);
            $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
            $table->addColumn('name', Types::STRING)->setLength(255)->setNotnull(true)->setComment(
                'Name of the value definition'
            );
            $table->addColumn('regvalue', Types::TEXT)->setNotnull(false)->setComment($comment);

            $this->setPrimaryKey($table, ['id']);

            $table->addIndex(['hardware_id'], 'registry_hardware_id_idx');
            $table->addIndex(['name'], 'registry_name_idx');
        }

        // Foreign key constraints did not exist in previous schema. Add them unconditionally.
        $this->addClientForeignKey($schema->getTable(Table::RegistryData), 'fk_registry_hardware_id');
        $schema->getTable(Table::RegistryData)->addForeignKeyConstraint(
            Table::RegistryValueDefinitions,
            ['name'],
            ['name'],
            ['onDelete' => ReferentialAction::CASCADE->value],
            'fk_registry_regconfig_name',
        );
    }
}
