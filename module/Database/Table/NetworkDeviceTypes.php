<?php

/**
 * "devicetype" table
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

use Model\Network\DeviceManager;
use Psr\Container\ContainerInterface;

/**
 * "devicetype" table
 */
class NetworkDeviceTypes extends \Database\AbstractTable
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'devicetype';
        parent::__construct($container);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function postSetSchema($schema, $database, $prune)
    {
        // Create entries for orphaned types in NetworkDevicesIdentified table
        if (isset($database->getTables()['network_devices'])) {
            $definedTypes = $this->fetchCol('name');
            foreach ($this->adapter->query('SELECT DISTINCT type FROM network_devices')->execute() as $type) {
                $type = $type['type'];
                if (!in_array($type, $definedTypes)) {
                    $this->logger->notice(sprintf('Creating undefined network device type "%s"', $type));
                    $this->container->get(DeviceManager::class)->addType($type);
                }
            }
        }
    }
}
