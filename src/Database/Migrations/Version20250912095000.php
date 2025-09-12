<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
use Override;

/** @codeCoverageIgnore */
final class Version20250912095000 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateTableDrop, 'itmgmt_comments');
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->sm->tableExists('itmgmt_comments')) {
            $schema->dropTable('itmgmt_comments');
        }
    }
}
