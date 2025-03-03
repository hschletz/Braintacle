<?php

declare(strict_types=1);

namespace <namespace>;

use Braintacle\Database\Migration;
use Doctrine\DBAL\Schema\Schema;
use Override;

/** @codeCoverageIgnore */
final class <className> extends Migration
{
    #[Override]
    public function getDescription(): string
    {

    }

    #[Override]
    public function up(Schema $schema): void
    {
        <up>
    }
}
