<?php

/**
 * "download_enable" view
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

use Doctrine\DBAL\Schema\View;
use Nada\Column\AbstractColumn as Column;

/**
 * "download_enable" view
 * @deprecated provides view for legacy code only
 */
class PackageDownloadInfo extends \Database\AbstractTable
{
    const TABLE = 'download_enable';

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        // Reimplementation to provide a view instead of previous table

        $logger = $this->_serviceLocator->get('Library\Logger');
        $nada = $this->_serviceLocator->get('Database\Nada');

        if (in_array('download_enable', $nada->getTableNames())) {
            // Use value of "fileid" column instead of obsolete "id" for package assignments
            $logger->info('Transforming package assignment IDs...');
            $where = new \Laminas\Db\Sql\Where();
            $this->_serviceLocator->get('Database\Table\ClientConfig')->update(
                array(
                    'ivalue' => new \Laminas\Db\Sql\Expression(
                        sprintf(
                            '(SELECT CAST(fileid AS %s) FROM download_enable WHERE id = ivalue)',
                            $nada->getNativeDatatype(Column::TYPE_INTEGER, 32, true)
                        )
                    )
                ),
                $where->notEqualTo('name', 'DOWNLOAD_SWITCH')->like('name', 'DOWNLOAD%')
            );
            $logger->info('done.');

            $logger->info("Dropping table 'download_enable'...");
            $nada->dropTable('download_enable');
            $logger->info('done.');
        }

        $schema = $this->connection->getSchemaManager();
        if (!$schema->hasView(static::TABLE)) {
            $logger->info("Creating view 'download_enable'");

            $platform = $this->connection->getDatabasePlatform();
            $nullCast = 'CAST(NULL AS %s)';
            $typeText = sprintf($nullCast, $platform->getVarcharTypeDeclarationSQL(['length' => 255]));
            $typeInt = sprintf($nullCast, $platform->getIntegerTypeDeclarationSQL([]));

            $query = $this->connection->createQueryBuilder();
            $query->select(
                'fileid AS id',
                'fileid',
                "(SELECT tvalue FROM config WHERE name = 'BRAINTACLE_DEFAULT_INFOFILE_LOCATION') AS info_loc",
                "(SELECT tvalue FROM config WHERE name = 'BRAINTACLE_DEFAULT_DOWNLOAD_LOCATION') AS pack_loc",
                $typeText . ' AS cert_path',
                $typeText . ' AS cert_file',
                $typeInt . ' AS server_id'
            )->from(Packages::TABLE);

            $view = new View(static::TABLE, $query->getSQL());
            $schema->createView($view);

            $logger->info('done.');
        }

        // Temporary workaround for tests
        if (!in_array(static::TABLE, $nada->getViewNames())) {
            $nada->createView(static::TABLE, $query->getSQL());
        }
    }
}
