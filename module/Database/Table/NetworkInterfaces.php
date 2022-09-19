<?php

/**
 * "networks" table
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
 * "networks" table
 */
class NetworkInterfaces extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'networks';

        $hydrationMap = [
            'description' => 'Description',
            'speed' => 'Rate',
            'macaddr' => 'MacAddress',
            'ipaddress' => 'IpAddress',
            'ipmask' => 'Netmask',
            'ipgateway' => 'Gateway',
            'ipsubnet' => 'Subnet',
            'ipdhcp' => 'DhcpServer',
            'status' => 'Status',
            'type' => 'Type',
            'typemib' => 'TypeMib',
            'is_blacklisted' => 'IsBlacklisted',
        ];
        // Don't extract the virtual IsBlacklisted property
        $extractionMap = array_flip($hydrationMap);
        unset($extractionMap['IsBlacklisted']);

        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();

        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy($hydrationMap, $extractionMap)
        );
        $this->_hydrator->addFilter('whitelist', new \Library\Hydrator\Filter\Whitelist(array_keys($extractionMap)));

        $this->_hydrator->addStrategy('MacAddress', new \Library\Hydrator\Strategy\MacAddress());
        $this->_hydrator->addStrategy('macaddr', new \Library\Hydrator\Strategy\MacAddress());

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Client\Item\NetworkInterface')
        );

        parent::__construct($serviceLocator);
    }
}
