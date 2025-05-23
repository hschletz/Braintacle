<?php

/**
 * "download_enable" view
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Database\Table;

use Nada\Column\AbstractColumn as Column;
use Laminas\Db\Sql\Literal;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * "download_enable" view
 * @deprecated provides view for legacy code only
 */
class PackageDownloadInfo extends \Database\AbstractTable
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'download_enable';
        parent::__construct($container);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        // Reimplementation to provide a view instead of previous table

        $logger = $this->container->get(LoggerInterface::class);
        $database = $this->container->get(AbstractDatabase::class);

        if (in_array('download_enable', $database->getTableNames())) {
            // Use value of "fileid" column instead of obsolete "id" for package assignments
            $logger->info('Transforming package assignment IDs...');
            $where = new \Laminas\Db\Sql\Where();
            $this->container->get(ClientConfig::class)->update(
                array(
                    'ivalue' => new \Laminas\Db\Sql\Expression(
                        sprintf(
                            '(SELECT CAST(fileid AS %s) FROM download_enable WHERE id = ivalue)',
                            $database->getNativeDatatype(Column::TYPE_INTEGER, 32, true)
                        )
                    )
                ),
                $where->notEqualTo('name', 'DOWNLOAD_SWITCH')->like('name', 'DOWNLOAD%')
            );
            $logger->info('done.');

            $logger->info("Dropping table 'download_enable'...");
            $database->dropTable('download_enable');
            $logger->info('done.');
        }

        if (!in_array('download_enable', $database->getViewNames())) {
            $logger->info("Creating view 'download_enable'");
            $typeText = $database->getNativeDatatype(Column::TYPE_VARCHAR, 255, true);
            $typeInt = $database->getNativeDatatype(Column::TYPE_INTEGER, 32, true);
            $null = 'CAST(NULL AS %s)';
            $sql = $this->container->get(Packages::class)->getSql();
            $select = $sql->select();
            $select->columns(
                array(
                    'id' => 'fileid',
                    'fileid' => 'fileid',
                    'info_loc' => new Literal(
                        "(SELECT tvalue FROM config WHERE name = 'BRAINTACLE_DEFAULT_INFOFILE_LOCATION')"
                    ),
                    'pack_loc' => new Literal(
                        "(SELECT tvalue FROM config WHERE name = 'BRAINTACLE_DEFAULT_DOWNLOAD_LOCATION')"
                    ),
                    'cert_path' => new Literal(sprintf($null, $typeText)),
                    'cert_file' => new Literal(sprintf($null, $typeText)),
                    'server_id' => new Literal(sprintf($null, $typeInt)),
                ),
                false
            );
            $database->createView('download_enable', $sql->buildSqlString($select));
            $logger->info('done.');
        }
    }
}
