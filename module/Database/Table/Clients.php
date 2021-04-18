<?php

/**
 * "clients" view
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

/**
 * "clients" view
 *
 * This view provides all clients and should be used for any SELECT queries on
 * clients. It contains data from the "ClientsAndGroups" and "ClientSystemInfo"
 * tables, excluding group entries, Windows-specific data and useless columns.
 */
class Clients extends \Database\AbstractTable
{
    const TABLE = 'clients';

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
        return parent::initialize();
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
            $logger->info("Creating view 'clients'");

            $query = $this->connection->createQueryBuilder();
            $query->select(
                'h.id',
                'h.deviceid',
                'h.uuid',
                'h.name',
                'h.userid',
                'h.osname',
                'h.osversion',
                'h.oscomments',
                'h.description',
                'h.processort',
                'h.processors',
                'h.processorn',
                'h.memory',
                'h.swap',
                'h.dns',
                'h.defaultgateway',
                'h.lastdate',
                'h.lastcome',
                'h.useragent',
                'h.checksum',
                'h.ipaddr', // deprecated
                '(CASE WHEN h.winprodid IS NULL THEN h.workgroup ELSE NULL END) AS dns_domain',
                'b.smanufacturer',
                'b.smodel',
                'b.ssn',
                'b.assettag',
                'b.type',
                'b.bversion',
                'b.bdate',
                'b.bmanufacturer'
            )->from(ClientsAndGroups::TABLE, 'h')
            ->leftJoin('h', ClientSystemInfo::TABLE, 'b', 'b.hardware_id = h.id')
            ->where("deviceid != '_SYSTEMGROUP_'");

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
