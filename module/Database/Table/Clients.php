<?php

/**
 * "clients" view
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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
        $this->_hydrator = new \Database\Hydrator\Clients($this->_serviceLocator);
        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $this->_serviceLocator->get('Model\Client\Client')
        );

        parent::initialize();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        // Reimplementation to provide a view
        $logger = $this->_serviceLocator->get('Library\Logger');
        $database = $this->_serviceLocator->get('Database\Nada');
        if (!in_array('clients', $database->getViewNames())) {
            $logger->info("Creating view 'clients'");
            $sql = $this->_serviceLocator->get('Database\Table\ClientsAndGroups')->getSql();
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
