<?php

/**
 * "clients" view
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

use Database\Hydrator\Clients as ClientsHydrator;
use Model\Client\Client;
use Nada\Database\AbstractDatabase;
use Psr\Log\LoggerInterface;

/**
 * "clients" view
 *
 * This view provides all clients and should be used for any SELECT queries on
 * clients. It contains data from the "ClientsAndGroups" and "ClientSystemInfo"
 * tables, excluding group entries, Windows-specific data and useless columns.
 */
class Clients extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function initialize()
    {
        $this->_hydrator = new ClientsHydrator($this->container);
        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $this->container->get(Client::class)
        );

        parent::initialize();

        return; // satisfy parent return type declaration
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
        if (!in_array('clients', $database->getViewNames())) {
            $logger->info("Creating view 'clients'");
            $sql = $this->container->get(ClientsAndGroups::class)->getSql();
            $select = $sql->select();
            $select->columns(
                array(
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
                    'dns_domain' => new \Laminas\Db\Sql\Literal(
                        'CASE WHEN winprodid IS NULL THEN workgroup ELSE NULL END'
                    )
                ),
                false
            )->join(
                'bios',
                'hardware_id = id',
                array('smanufacturer', 'smodel', 'ssn', 'assettag', 'type', 'bversion', 'bdate', 'bmanufacturer'),
                \Laminas\Db\Sql\Select::JOIN_LEFT
            )->where(new \Laminas\Db\Sql\Predicate\Operator('deviceid', '!=', '_SYSTEMGROUP_'));

            $database->createView('clients', $sql->buildSqlString($select));
            $logger->info('done.');
        }
    }
}
