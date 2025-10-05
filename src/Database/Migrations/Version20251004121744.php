<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20251004121744 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, Table::Locks);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(Table::Locks)) {
            return;
        }

        $table = $this->createTable($schema, Table::Locks, 'Advisory locks for clients and groups', self::EngineMemory);
        $table
            ->addColumn('hardware_id', Types::INTEGER)
            ->setNotnull(true)
            ->setComment('Client or group ID');
        $table
            ->addColumn('id', Types::INTEGER)
            ->setNotnull(false)
            ->setComment('obsolete, for compatibility with server code');
        $table
            ->addColumn('since', Types::DATETIME_IMMUTABLE)
            ->setNotnull(true)
            ->setComment('Timestamp of lock creation');

        $this->setPrimaryKey($table, ['hardware_id']);
        $table->addIndex(['since'], 'locks_since_idx');
    }
}
