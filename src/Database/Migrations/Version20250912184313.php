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
final class Version20250912184313 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Create various tables for client deletion';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if (!$this->tableExists(TableName::AndroidEnvironments)) {
            $tableEditor = Table::editor()
                ->setUnquotedName(TableName::AndroidEnvironments)
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment('Android environment');

            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('hardware_id')
                    ->setTypeName(Types::INTEGER)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('javaname')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('javacountry')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('javaclasspath')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('javahome')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->create()
            );

            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id')->create()
            );

            foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
                $this->addSql($sql);
            }
        }

        if (!$this->tableExists(TableName::CustomFields)) {
            $tableEditor = Table::editor()
                ->setUnquotedName(TableName::CustomFields)
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment('Userdefined fields. Additional columns may be added by the user.');

            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('hardware_id')
                    ->setTypeName(Types::INTEGER)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('tag')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('Default field with some special treatment')
                    ->create()
            );

            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setUnquotedColumnNames('hardware_id')->create()
            );

            foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
                $this->addSql($sql);
            }
        }

        if (!$this->tableExists(TableName::NetworkDevicesIdentified)) {
            $tableEditor = Table::editor()
                ->setUnquotedName(TableName::NetworkDevicesIdentified)
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment('Identified network devices');

            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('macaddr')
                    ->setTypeName(Types::STRING)
                    ->setLength(17)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('description')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('type')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('references NetworkDeviceTypes table')
                    ->create()
            );

            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setUnquotedColumnNames('macaddr')->create()
            );

            foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
                $this->addSql($sql);
            }
        }

        if (!$this->tableExists(TableName::NetworkDevicesScanned)) {
            $tableEditor = Table::editor()
                ->setUnquotedName(TableName::NetworkDevicesScanned)
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment('Scanned MAC/IP addresses');

            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('mac')
                    ->setTypeName(Types::STRING)
                    ->setLength(17)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('ip')
                    ->setTypeName(Types::STRING)
                    ->setLength(15)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('netid')
                    ->setTypeName(Types::STRING)
                    ->setLength(15)
                    ->setNotNull(true)
                    ->setComment('Network address')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('mask')
                    ->setTypeName(Types::STRING)
                    ->setLength(15)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('name')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('DNS name (IP address if it does not resolve)')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('date')
                    ->setTypeName(Types::DATETIME_IMMUTABLE)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('tag')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->create()
            );

            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setUnquotedColumnNames('mac')->create()
            );

            $tableEditor->addIndex(
                Index::editor()
                    ->setUnquotedColumnNames('netid', 'mask')
                    ->setUnquotedName('netmap_netid_mask_idx')
                    ->create()
            );

            foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
                $this->addSql($sql);
            }
        }

        if (!$this->tableExists(TableName::NetworkInterfaces)) {
            $tableEditor = Table::editor()
                ->setUnquotedName(TableName::NetworkInterfaces)
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment("Client's network interfaces");

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
                    ->setUnquotedName('description')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('type')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('Part of type description (Ethernet, Wireless) - see also typemib')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('typemib')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('Part of type description (Ethernet, Wireless) - see also type')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('speed')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment("Textual representation of data rate, including unit. Example: '100 Mb/s', '1 Gb/s'")
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('macaddr')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('MAC address, lowercase, colon-separated')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('status')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('One of Up/Down')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('ipaddress')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('IPv4 address, dot syntax')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('ipmask')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('IPv4 netmask, dot syntax')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('ipgateway')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('IPv4 gateway, dot syntax')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('ipsubnet')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('IPv4 network address, dot syntax')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('ipdhcp')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('IPv4 address of DHCP server for current subnet')
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('mtu')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->setNotNull(false)
                    ->setComment('Link MTU')
                    ->create()
            );

            $tableEditor->addPrimaryKeyConstraint(
                PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create()
            );

            $tableEditor->addIndex(
                Index::editor()
                    ->setUnquotedColumnNames('hardware_id')
                    ->setUnquotedName('networks_hardware_id_idx')
                    ->create()
            );
            $tableEditor->addIndex(
                Index::editor()->setUnquotedColumnNames('macaddr')->setUnquotedName('networks_macaddr_idx')->create()
            );
            $tableEditor->addIndex(
                Index::editor()
                    ->setUnquotedColumnNames('ipaddress')
                    ->setUnquotedName('networks_ipaddress_idx')
                    ->create()
            );
            $tableEditor->addIndex(
                Index::editor()->setUnquotedColumnNames('ipmask')->setUnquotedName('networks_ipmask_idx')->create()
            );
            $tableEditor->addIndex(
                Index::editor()->setUnquotedColumnNames('ipsubnet')->setUnquotedName('networks_ipsubnet_idx')->create()
            );

            foreach ($this->platform->getCreateTableSQL($tableEditor->create()) as $sql) {
                $this->addSql($sql);
            }
        }

        if (!$this->tableExists(TableName::WindowsProductKeys)) {
            $tableEditor = Table::editor()
                ->setUnquotedName('braintacle_windows')
                ->setOptions(['engine' => self::EngineInnoDb])
                ->setComment('Windows-specific client information');

            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('hardware_id')
                    ->setTypeName(Types::INTEGER)
                    ->setNotNull(true)
                    ->create()
            );
            $tableEditor->addColumn(
                Column::editor()
                    ->setUnquotedName('manual_product_key')
                    ->setTypeName(Types::STRING)
                    ->setLength(29)
                    ->setNotNull(false)
                    ->setComment('Product key manually entered in the console if detected key is incorrect')
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
}
