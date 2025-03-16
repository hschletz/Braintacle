<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Override;

/** @codeCoverageIgnore */
final class Version20250315134219 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTable, 'groups_cache');
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->tableExists('groups_cache')) {
            return;
        }

        $table = $this->createTable($schema, 'groups_cache', 'Group memberships');

        $table->addColumn('hardware_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('group_id', Types::INTEGER)->setNotnull(true);
        $table->addColumn('static', Types::INTEGER)->setNotnull(false)->setDefault(0)->setComment(
            'Membership type: 0=automatic (cached), 1=manual, 2=excluded'
        );

        $table->setPrimaryKey(['hardware_id', 'group_id']);
    }
}
