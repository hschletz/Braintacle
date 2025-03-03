<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
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

        $table = $this->createTable($schema, 'devices', 'Configuration for specific clients and groups');

        $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('name', Types::STRING)->setLength(50)->setNotnull(true);
        $table->addColumn('ivalue', Types::INTEGER)->setNotnull(true);
        $table->addColumn('tvalue', types::STRING)->setLength(255)->setNotnull(false);
        $table->addColumn('comments', Types::TEXT)->setNotnull(false)->setComment(
            "Timestamp of last package status change in perl's localtime() format"
        );

        $table->setPrimaryKey(['hardware_id', 'name', 'ivalue']);

        $table->addIndex(['hardware_id'], 'devices_hardware_id_idx');
        $table->addIndex(['name'], 'devices_name_idx');
        $table->addIndex(['ivalue'], 'devices_ivalue_idx');
        $table->addIndex(['tvalue'], 'devices_tvalue_idx');
    }
}
