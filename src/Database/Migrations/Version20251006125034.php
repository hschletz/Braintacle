<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\View;
use Override;

/** @codeCoverageIgnore */
final class Version20251006125034 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateView, Table::Groups);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $view = new View(Table::Groups, '
            SELECT
                id,
                name,
                description,
                lastdate AS creation_date,
                create_time AS cache_creation_date,
                revalidate_from AS cache_expiration_date,
                request AS dynamic_members_sql
            FROM groups
            JOIN hardware ON hardware.id = groups.hardware_id
        ');
        $this->sm->createView($view);
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->sm->dropView(Table::Groups);
    }
}
