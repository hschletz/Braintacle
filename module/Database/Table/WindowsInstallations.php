<?php

/**
 * "windows_installations" view
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

use Database\Connection;
use Doctrine\DBAL\Schema\View;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * "windows_installations" view
 */
class WindowsInstallations extends \Database\AbstractTable
{
    const TABLE = 'windows_installations';

    public function __construct(ServiceLocatorInterface $serviceLocator, Connection $connection = null)
    {
        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'workgroup' => 'Workgroup',
                    'user_domain' => 'UserDomain',
                    'company' => 'Company',
                    'owner' => 'Owner',
                    'product_key' => 'ProductKey',
                    'product_id' => 'ProductId',
                    'manual_product_key' => 'ManualProductKey',
                    'cpu_architecture' => 'CpuArchitecture',
                )
            )
        );

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Client\WindowsInstallation')
        );

        parent::__construct($serviceLocator, $connection);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        // Reimplementation to provide a view
        $schema = $this->connection->getSchemaManager();
        if (!$schema->hasView(static::TABLE)) {
            $logger = $this->_serviceLocator->get('Library\Logger');
            $logger->info("Creating view 'windows_installations'");

            $query = $this->connection->createQueryBuilder();
            $query->select(
                'h.id AS client_id',
                'h.workgroup',
                'h.userdomain AS user_domain',
                'h.wincompany AS company',
                'h.winowner AS owner',
                'h.winprodkey AS product_key',
                'w.manual_product_key',
                'h.winprodid AS product_id',
                'h.arch AS cpu_architecture',
            )
            ->from(ClientsAndGroups::TABLE, 'h')
            ->leftJoin('h', WindowsProductKeys::TABLE, 'w', 'w.hardware_id = h.id')
            ->where('h.winprodid IS NOT NULL');

            $view = new View(static::TABLE, $query->getSQL());
            $schema->createView($view);

            $logger->info('done.');
        }

        // Temporary workaround for tests
        $nada = $this->_serviceLocator->get('Database\Nada');
        if (!in_array(static::TABLE, $nada->getViewNames())) {
            $nada->createView(static::TABLE, $query->getSQL());
        }
    }
}
