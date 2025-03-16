<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
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

        $table = $this->createTable($schema, 'hardware', 'Clients and groups');

        $table->addColumn('id', Types::INTEGER)->setNotnull(true)->setAutoincrement(true);
        $table->addColumn('deviceid', Types::STRING)->setLength(255)->setNotnull(true)->setComment(
            'Client-generated uniqe ID. Groups have special value _SYSTEMGROUP_.'
        );
        $table->addColumn('uuid', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'System UUID reported by agent'
        );
        $table->addColumn('name', Types::STRING)->setLength(255)->setNotnull(true)->setComment(
            'Client or group name'
        );
        $table->addColumn('workgroup', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Windows workgroup/domain'
        );
        $table->addColumn('userid', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Name of active user'
        );
        $table->addColumn('userdomain', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Windows domain of active user (computer name for local account)'
        );
        $table->addColumn('osname', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'OS name reported by agent'
        );
        $table->addColumn('osversion', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'OS version number'
        );
        $table->addColumn('oscomments', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'OS specific version string: service pack (Windows), kernel version (Linux)...'
        );
        $table->addColumn('description', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'OS comment for clients, description for groups'
        );
        $table->addColumn('wincompany', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Company name entered at Windows setup'
        );
        $table->addColumn('winowner', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Owner name entered at Windows setup'
        );
        $table->addColumn('winprodid', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Windows Product ID'
        );
        $table->addColumn('winprodkey', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Windows product key (reported by agent)'
        );
        $table->addColumn('arch', Types::STRING)->setLength(30)->setNotnull(false)->setComment(
            'Windows CPU architecture'
        );
        $table->addColumn('processort', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'CPU type'
        );
        $table->addColumn('processors', Types::INTEGER)->setNotnull(false)->setComment(
            'CPU clock in MHz'
        );
        $table->addColumn('processorn', Types::SMALLINT)->setNotnull(false)->setComment(
            'Amount of CPU cores'
        );
        $table->addColumn('memory', Types::INTEGER)->setNotnull(false)->setComment(
            'Maximum amount of RAM avalilable to OS (in MB). May be lower than physical RAM.'
        );
        $table->addColumn('swap', Types::INTEGER)->setNotnull(false)->setComment(
            'Amount of swap space (in MB)'
        );
        $table->addColumn('ipaddr', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'deprecated'
        );
        $table->addColumn('ipsrc', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'deprecated'
        );
        $table->addColumn('dns', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'IPv4 address of primary DNS server (UNIX agent only)'
        );
        $table->addColumn('defaultgateway', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'IPv4 address of default gateway (UNIX agent only)'
        );
        $table->addColumn('etime', Types::INTEGER)->setNotnull(false)->setComment(
            'deprecated'
        );
        $table->addColumn('lastdate', Types::DATETIME_IMMUTABLE)->setNotnull(false)->setComment(
            'Timestamp of last inventory'
        );
        $table->addColumn('lastcome', Types::DATETIME_IMMUTABLE)->setNotnull(false)->setComment(
            'Timestamp of last agent contact'
        );
        $table->addColumn('useragent', Types::STRING)->setLength(50)->setNotnull(false)->setComment(
            'Agent ID sting'
        );
        $table->addColumn('quality', Types::DECIMAL)->setPrecision(7)->setScale(4)->setNotnull(false)->setComment(
            'deprecated'
        );
        $table->addColumn('fidelity', Types::BIGINT)->setNotnull(false)->setDefault(1)->setComment(
            'deprecated'
        );
        $table->addColumn('type', Types::INTEGER)->setNotnull(false)->setComment(
            'deprecated'
        );
        $table->addColumn('checksum', Types::BIGINT)->setNotnull(false)->setDefault(262143);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['deviceid'], 'hardware_deviceid_idx');
        $table->addIndex(['name'], 'hardware_name_idx');
        $table->addIndex(['memory'], 'hardware_memory_idx');
        $table->addIndex(['osname'], 'hardware_osname_idx');
        $table->addIndex(['userid'], 'hardware_userid_idx');
        $table->addIndex(['workgroup'], 'hardware_workgroup_idx');
        $table->addIndex(['checksum'], 'hardware_checksum_idx');
        $table->addUniqueConstraint(['name', 'deviceid'], 'hardware_name_clientid_unique_idx');
    }
}
