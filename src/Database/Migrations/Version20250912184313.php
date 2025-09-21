<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
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
        if (!$this->tableExists(Table::AndroidEnvironments)) {
            $table = $this->createTable(
                $schema,
                Table::AndroidEnvironments,
                'Android environment',
            );

            $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
            $table->addColumn('javaname', types::STRING)->setLength(255)->setNotnull(false);
            $table->addColumn('javacountry', types::STRING)->setLength(255)->setNotnull(false);
            $table->addColumn('javaclasspath', types::STRING)->setLength(255)->setNotnull(false);
            $table->addColumn('javahome', types::STRING)->setLength(255)->setNotnull(false);

            $this->setPrimaryKey($table, ['hardware_id']);
        }

        if (!$this->tableExists(Table::CustomFields)) {
            $table = $this->createTable(
                $schema,
                Table::CustomFields,
                'Userdefined fields. Additional columns may be added by the user.',
            );

            $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
            $table
                ->addColumn('tag', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('Default field with some special treatment');

            $this->setPrimaryKey($table, ['hardware_id']);
        }

        if (!$this->tableExists(Table::NetworkDevicesIdentified)) {
            $table = $this->createTable(
                $schema,
                Table::NetworkDevicesIdentified,
                'Identified network devices',
            );
            $table->addColumn('macaddr', types::STRING)->setLength(17)->setNotnull(false);
            $table->addColumn('description', types::STRING)->setLength(255)->setNotnull(false);
            $table
                ->addColumn('type', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('references NetworkDeviceTypes table');

            $this->setPrimaryKey($table, ['macaddr']);
        }

        if (!$this->tableExists(Table::NetworkDevicesScanned)) {
            $table = $this->createTable(
                $schema,
                Table::NetworkDevicesScanned,
                'Scanned MAC/IP addresses',
            );
            $table->addColumn('mac', types::STRING)->setLength(17)->setNotnull(true);
            $table->addColumn('ip', types::STRING)->setLength(15)->setNotnull(true);
            $table->addColumn('netid', types::STRING)->setLength(15)->setNotnull(true)->setComment('Network address');
            $table->addColumn('mask', types::STRING)->setLength(15)->setNotnull(true);
            $table
                ->addColumn('name', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('DNS name (IP address if it does not resolve)');
            $table->addColumn('date', types::DATETIME_IMMUTABLE)->setNotnull(true);
            $table->addColumn('tag', types::STRING)->setLength(255)->setNotnull(false);

            $this->setPrimaryKey($table, ['mac']);

            $table->addIndex(['netid', 'mask'], 'netmap_netid_mask_idx');
        }

        if (!$this->tableExists(Table::NetworkInterfaces)) {
            $table = $this->createTable(
                $schema,
                Table::NetworkInterfaces,
                "Client's network interfaces",
            );

            $table->addColumn('id', Types::INTEGER)->setNotnull(true)->setAutoincrement(true);
            $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
            $table->addColumn('description', types::STRING)->setLength(255)->setNotnull(true);
            $table
                ->addColumn('type', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('Part of type description (Ethernet, Wireless) - see also typemib');
            $table
                ->addColumn('typemib', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('Part of type description (Ethernet, Wireless) - see also type');
            $table
                ->addColumn('speed', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment("Textual representation of data rate, including unit. Example: '100 Mb/s', '1 Gb/s'");
            $table
                ->addColumn('macaddr', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('MAC address, lowercase, colon-separated');
            $table
                ->addColumn('status', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('One of Up/Down');
            $table
                ->addColumn('ipaddress', types::STRING)
                ->setLength(255)
                ->setNotnull(false);
            $table
                ->addColumn('ipmask', types::STRING)
                ->setLength(255)
                ->setNotnull(false);
            $table
                ->addColumn('ipgateway', types::STRING)
                ->setLength(255)
                ->setNotnull(false);
            $table
                ->addColumn('ipsubnet', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('Network address');
            $table
                ->addColumn('ipdhcp', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('IP address of DHCP server for current subnet');
            $table
                ->addColumn('mtu', types::STRING)
                ->setLength(255)
                ->setNotnull(false)
                ->setComment('Link MTU');

            $this->setPrimaryKey($table, ['id']);

            $table->addIndex(['hardware_id'], 'networks_hardware_id_idx');
            $table->addIndex(['macaddr'], 'networks_macaddr_idx');
            $table->addIndex(['ipaddress'], 'networks_ipaddress_idx');
            $table->addIndex(['ipmask'], 'networks_ipmask_idx');
            $table->addIndex(['ipsubnet'], 'networks_ipsubnet_idx');
        }

        if (!$this->tableExists(Table::WindowsProductKeys)) {
            $table = $this->createTable(
                $schema,
                Table::WindowsProductKeys,
                'Windows-specific client information',
            );

            $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
            $table
                ->addColumn('manual_product_key', types::STRING)
                ->setLength(29)
                ->setNotnull(false)
                ->setComment('Product key manually entered in the console if detected key is incorrect');

            $this->setPrimaryKey($table, ['hardware_id']);
        }
    }
}
