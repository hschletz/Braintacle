<?php

/**
 * "netmap" table
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use Model\Network\Device;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;

/**
 * "netmap" table
 */
class NetworkDevicesScanned extends \Database\AbstractTable
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'netmap';

        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'ip' => 'IpAddress',
                    'mac' => 'MacAddress',
                    'name' => 'Hostname',
                    'date' => 'DiscoveryDate',
                    'description' => 'Description',
                    'type' => 'Type',
                )
            )
        );

        $dateTimeFormatter = new \Laminas\Hydrator\Strategy\DateTimeFormatterStrategy(
            $container->get(AbstractDatabase::class)->timestampFormatPhp(),
            new \DateTimeZone('UTC')
        );
        $this->_hydrator->addStrategy('DiscoveryDate', $dateTimeFormatter);
        $this->_hydrator->addStrategy('date', $dateTimeFormatter);

        $macAddress = new \Library\Hydrator\Strategy\MacAddress();
        $this->_hydrator->addStrategy('MacAddress', $macAddress);
        $this->_hydrator->addStrategy('mac', $macAddress);

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $container->get(Device::class)
        );
        parent::__construct($container);
    }
}
