<?php

/**
 * "software_installations" view.
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

use Model\Client\Item\Software as SoftwareItem;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * "software_installations" view.
 *
 * Joins the software names from the "software_definitions" table and sanitizes
 * column names.
 */
class Software extends \Database\AbstractTable
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'software_installations';

        $this->_hydrator = new \Database\Hydrator\Software();

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $container->get(SoftwareItem::class)
        );

        parent::__construct($container);
    }

    /** {@inheritdoc} */
    public function delete($where)
    {
        // This is a view. Forward operation to underlying table.
        return $this->getContainer()->get(SoftwareRaw::class)->delete($where);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        // Reimplementation to provide a view

        // Create/update softwareRaw table first because this view depends on it.
        $softwareRaw = $this->container->get(SoftwareRaw::class);
        $softwareRaw->updateSchema($prune);

        $logger = $this->container->get(LoggerInterface::class);
        $database = $this->container->get(AbstractDatabase::class);
        if (!in_array('software_installations', $database->getViewNames())) {
            $logger->info("Creating view 'software_installations'");
            $sql = $softwareRaw->getSql();
            $select = $sql->select();
            $select->columns(
                [
                    'id',
                    'hardware_id',
                    'version',
                    'comment' => 'comments',
                    'publisher',
                    'install_location' => 'folder',
                    'is_hotfix' => 'source',
                    'guid',
                    'language',
                    'installation_date' => 'installdate',
                    'architecture' => 'bitswidth',
                    'size' => 'filesize',
                ],
                true
            )->join(
                'software_definitions',
                'software.definition_id = software_definitions.id',
                ['name', 'display'],
                \Laminas\Db\Sql\Select::JOIN_INNER
            );

            $database->createView('software_installations', $sql->buildSqlString($select));
            $logger->info('done.');
        }
    }
}
