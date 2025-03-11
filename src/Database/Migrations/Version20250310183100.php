<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250310183100 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, 'download_history');
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists('download_history')) {
            return;
        }

        $table = $this->createTable($schema, 'download_history', 'Packages already downloaded by a client');

        $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('pkg_id', Types::INTEGER)->setNotnull(true);

        $table->setPrimaryKey(['hardware_id', 'pkg_id']);
    }
}
