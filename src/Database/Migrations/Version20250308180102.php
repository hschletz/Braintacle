<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250308180102 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, 'download_available');
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists('download_available')) {
            return;
        }

        $table = $this->createTable($schema, 'download_available', 'Packages');

        $table->addColumn('fileid', Types::INTEGER)->setNotnull(true)->setComment(
            'Unix timestamp of package creation, also used as PK'
        );
        $table->addColumn('name', Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn('priority', Types::INTEGER)->setNotnull(true);
        $table->addColumn('fragments', Types::INTEGER)->setNotnull(true);
        $table->addColumn('size', Types::INTEGER)->setNotnull(true);
        $table->addColumn('osname', Types::STRING)->setLength(7)->setNotnull(true)->setComment('WINDOWS|LINUX|MacOSX');
        $table->addColumn('comment', Types::TEXT)->setNotnull(false);

        $table->setPrimaryKey(['fileid']);

        $table->addIndex(['name'], 'download_available_name_unique_idx');
    }
}
