<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250308180102 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, Table::Packages);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(Table::Packages)) {
            return;
        }

        $table = $this->createTable($schema, Table::Packages, 'Packages');

        $table->addColumn('fileid', Types::INTEGER)->setNotnull(true)->setComment(
            'Unix timestamp of package creation, also used as PK'
        );
        $table->addColumn('name', Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn('priority', Types::INTEGER)->setNotnull(true);
        $table->addColumn('fragments', Types::INTEGER)->setNotnull(true);
        $table->addColumn('size', Types::INTEGER)->setNotnull(true);
        $table->addColumn('osname', Types::STRING)->setLength(7)->setNotnull(true)->setComment('WINDOWS|LINUX|MacOSX');
        $table->addColumn('comment', Types::TEXT)->setNotnull(false);

        $this->setPrimaryKey($table, ['fileid']);

        $table->addIndex(['name'], 'download_available_name_unique_idx');
    }
}
