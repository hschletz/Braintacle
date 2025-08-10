<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250315135000 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, 'bios');
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists('bios')) {
            return;
        }

        $table = $this->createTable($schema, 'bios', "A client's system information provided by Firmware");

        $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('smanufacturer', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'System manufacturer name'
        );
        $table->addColumn('smodel', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Product name'
        );
        $table->addColumn('ssn', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Serial'
        );
        $table->addColumn('assettag', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Asset tag'
        );
        $table->addColumn('type', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Type (provided by manufacturer)'
        );
        $table->addColumn('bmanufacturer', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Firmware manufacturer name'
        );
        $table->addColumn('bversion', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Firmware version'
        );
        $table->addColumn('bdate', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Firmware date (no guarantee on format)'
        );
        $table->addColumn('mmanufacturer', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Mainboard manufacturer name'
        );
        $table->addColumn('mmodel', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Mainboard product name'
        );
        $table->addColumn('msn', Types::STRING)->setLength(255)->setNotnull(false)->setComment(
            'Mainboard serial number'
        );

        $this->setPrimaryKey($table, ['hardware_id']);

        $table->addIndex(['ssn'], 'bios_ssn_idx');
        $table->addIndex(['assettag'], 'bios_assettag_idx');
    }
}
