<?php

/**
 * "sounds" table
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

use Model\Client\Item\AudioDevice;
use Psr\Container\ContainerInterface;

/**
 * "sounds" table
 *
 * @psalm-suppress UnusedClass
 */
class AudioDevices extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'sounds';

        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'manufacturer' => 'Manufacturer',
                    'name' => 'Name',
                    'description' => 'Description',
                )
            )
        );

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $container->get(AudioDevice::class)
        );

        parent::__construct($container);
    }
}
