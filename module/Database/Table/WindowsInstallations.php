<?php

/**
 * "windows_installations" view
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

use Model\Client\WindowsInstallation;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * "windows_installations" view
 */
class WindowsInstallations extends \Database\AbstractTable
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
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
            $container->get(WindowsInstallation::class)
        );

        parent::__construct($container);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        // Reimplementation to provide a view
        $logger = $this->container->get(LoggerInterface::class);
        $database = $this->container->get(AbstractDatabase::class);
        if (!in_array('windows_installations', $database->getViewNames())) {
            $logger->info("Creating view 'windows_installations'");
            $sql = $this->container->get(ClientsAndGroups::class)->getSql();
            $select = $sql->select();
            $select->columns(
                array(
                    'client_id' => 'id',
                    'workgroup',
                    'user_domain' => 'userdomain',
                    'company' => 'wincompany',
                    'owner' => 'winowner',
                    'product_key' => 'winprodkey',
                    'product_id' => 'winprodid',
                    'cpu_architecture' => 'arch',
                ),
                false
            )->join(
                'braintacle_windows',
                'hardware_id = id',
                array('manual_product_key'),
                \Laminas\Db\Sql\Select::JOIN_LEFT
            )->where(new \Laminas\Db\Sql\Predicate\IsNotNull('winprodid'));

            $database->createView('windows_installations', $sql->buildSqlString($select));
            $logger->info('done.');
        }
    }
}
