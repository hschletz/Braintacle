<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250310183100 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, Table::PackageHistory);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(Table::PackageHistory)) {
            return;
        }

        $table = $this->createTable($schema, Table::PackageHistory, 'Packages already downloaded by a client');

        $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('pkg_id', Types::INTEGER)->setNotnull(true);

        $this->setPrimaryKey($table, ['hardware_id', 'pkg_id']);
    }
}
