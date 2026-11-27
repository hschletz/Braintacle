<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250315111256 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, 'hardware');
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists('hardware')) {
            return;
        }

        $tableEditor = Table::editor()
            ->setUnquotedName('hardware')
            ->setOptions(['engine' => self::EngineInnoDb])
            ->setComment('Clients and groups');

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
                ->setUnquotedName('deviceid')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(true)
                ->setComment(
                    'Client-generated uniqe ID. Groups have special values __SYSTEMGROUP__ and __DOWNLOADGROUP__.'
                )
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('uuid')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('System UUID reported by agent')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('name')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(true)
                ->setComment('Client or group name')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('workgroup')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Windows workgroup/domain')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('userid')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Name of active user')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('userdomain')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Windows domain of active user (computer name for local account)')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('osname')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('OS name reported by agent')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('osversion')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('OS version number')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('oscomments')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('OS specific version string: service pack (Windows), kernel version (Linux)...')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('description')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('OS comment for clients, description for groups')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('wincompany')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Company name entered at Windows setup')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('winowner')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Owner name entered at Windows setup')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('winprodid')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Windows Product ID')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('winprodkey')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('Windows product key (reported by agent)')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('arch')
                ->setTypeName(Types::STRING)
                ->setLength(30)
                ->setNotNull(false)
                ->setComment('Windows CPU architecture')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('processort')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('CPU type')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('processors')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(false)
                ->setComment('CPU clock in MHz')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('processorn')
                ->setTypeName(Types::SMALLINT)
                ->setNotNull(false)
                ->setComment('Amount of CPU cores')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('memory')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(false)
                ->setComment('Maximum amount of RAM avalilable to OS (in MB). May be lower than physical RAM.')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('swap')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(false)
                ->setComment('Amount of swap space (in MB)')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('ipaddr')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('deprecated')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('ipsrc')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('deprecated')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('dns')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('IPv4 address of primary DNS server (UNIX agent only)')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('defaultgateway')
                ->setTypeName(Types::STRING)
                ->setLength(255)
                ->setNotNull(false)
                ->setComment('IPv4 address of default gateway (UNIX agent only)')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('etime')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(false)
                ->setComment('deprecated')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('lastdate')
                ->setTypeName(Types::DATETIME_IMMUTABLE)
                ->setNotNull(false)
                ->setComment('Timestamp of last inventory')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('lastcome')
                ->setTypeName(Types::DATETIME_IMMUTABLE)
                ->setNotNull(false)
                ->setComment('Timestamp of last agent contact')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('useragent')
                ->setTypeName(Types::STRING)
                ->setLength(50)
                ->setNotNull(false)
                ->setComment('Agent ID sting')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('quality')
                ->setTypeName(Types::DECIMAL)
                ->setPrecision(7)
                ->setScale(4)
                ->setNotNull(false)
                ->setComment('deprecated')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('fidelity')
                ->setTypeName(Types::BIGINT)
                ->setNotNull(false)
                ->setDefaultValue(1)
                ->setComment('deprecated')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('type')
                ->setTypeName(Types::INTEGER)
                ->setNotNull(false)
                ->setComment('deprecated')
                ->create()
        );
        $tableEditor->addColumn(
            Column::editor()
                ->setUnquotedName('checksum')
                ->setTypeName(Types::BIGINT)
                ->setNotNull(false)
                ->setDefaultValue(262143)
                ->create()
        );

        $tableEditor->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create()
        );

        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('deviceid')->setUnquotedName('hardware_deviceid_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('name')->setUnquotedName('hardware_name_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('memory')->setUnquotedName('hardware_memory_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('osname')->setUnquotedName('hardware_osname_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('userid')->setUnquotedName('hardware_userid_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('workgroup')->setUnquotedName('hardware_workgroup_idx')->create()
        );
        $tableEditor->addIndex(
            Index::editor()->setUnquotedColumnNames('checksum')->setUnquotedName('hardware_checksum_idx')->create()
        );
        $tableEditor->addUniqueConstraint(
            UniqueConstraint::editor()
                ->setUnquotedColumnNames('name', 'deviceid')
                ->setUnquotedName('hardware_name_clientid_unique_idx')
                ->create()
        );

        foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
            $this->addSql($sql);
        }
    }
}
