<?php

/**
 * "cpus" table
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use Model\Client\Item\Cpu as CpuItem;
use Psr\Container\ContainerInterface;

/**
 * "cpus" table
 */
class Cpu extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'cpus';

        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'manufacturer' => 'Manufacturer',
                    'type' => 'Type',
                    'cores' => 'NumCores',
                    'cpuarch' => 'Architecture',
                    'data_width' => 'DataWidth',
                    'l2cachesize' => 'L2CacheSize',
                    'socket' => 'SocketType',
                    'speed' => 'NominalClock',
                    'current_speed' => 'CurrentClock',
                    'voltage' => 'Voltage',
                    'serialnumber' => 'Serial',
                )
            )
        );

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $container->get(CpuItem::class)
        );

        parent::__construct($container);
    }
}
