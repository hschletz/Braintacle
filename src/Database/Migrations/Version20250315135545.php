<?php

declare(strict_types=1);

namespace Braintacle\Database\Migrations;

use Braintacle\Database\Migration;
use Braintacle\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Override;

/** @codeCoverageIgnore */
final class Version20250315135545 extends Migration
{
    #[Override]
    public function getDescription(): string
    {
        return sprintf(self::TemplateView, Table::Clients);
    }

    #[Override]
    public function up(Schema $schema): void
    {
        if ($this->viewExists(Table::Clients)) {
            return;
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select(
                // columns from "hardware" table
                'id',
                'deviceid',
                'uuid',
                'name',
                'userid',
                'osname',
                'osversion',
                'oscomments',
                'description',
                'processort',
                'processors',
                'processorn',
                'memory',
                'swap',
                'dns',
                'defaultgateway',
                'lastdate',
                'lastcome',
                'useragent',
                'checksum',
                'ipaddr', // deprecated
                'CASE WHEN winprodid IS NULL THEN workgroup ELSE NULL END AS dns_domain',
                // columns from "bios" table
                'smanufacturer',
                'smodel',
                'ssn',
                'assettag',
                'b.type',
                'bversion',
                'bdate',
                'bmanufacturer',
            )->from('hardware', 'h')
            ->leftJoin(
                'h',
                'bios',
                'b',
                'hardware_id = id'
            )->where("deviceid != '_SYSTEMGROUP_'");

        $view = $this->createView(Table::Clients, $queryBuilder->getSQL());
        $this->sm->createView($view);
    }
}
