<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250315134219 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, Table::GroupMemberships);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists(Table::GroupMemberships)) {
            return;
        }

        $table = $this->createTable($schema, Table::GroupMemberships, 'Group memberships');

        $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('group_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('static', Types::INTEGER)->setNotnull(false)->setDefault(0)->setComment(
            'Membership type: 0=automatic (cached), 1=manual, 2=excluded'
        );

        $this->setPrimaryKey($table, ['hardware_id', 'group_id']);
    }
}
