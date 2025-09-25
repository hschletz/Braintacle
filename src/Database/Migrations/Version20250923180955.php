<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250923180955 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, Table::GroupInfo);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if (!$this->tableExists(Table::GroupInfo)) {
            $table = $schema->createTable(Table::GroupInfo);
            $table->setComment("Extra information on groups, supplementing the information in the 'hardware' table");

            $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
            $table->addColumn('request', Types::TEXT)->setNotnull(false)->setComment(
                'An SQL query that delivers client IDs for dynamic membership'
            );
            $table->addColumn('xmldef', Types::TEXT)->setNotnull(false)->setComment(
                'unused, only present for compatibility with the server code'
            );
            $table->addColumn('create_time', Types::INTEGER)->setNotnull(true)->setDefault(0)->setComment(
                'UNIX timestamp of last cache computation'
            );
            $table->addColumn('revalidate_from', Types::INTEGER)->setNotnull(true)->setDefault(0)->setComment(
                'create_time + random offset, used to determine time for next cache computation'
            );

            $this->setPrimaryKey($table, ['hardware_id']);
        }
    }
}
