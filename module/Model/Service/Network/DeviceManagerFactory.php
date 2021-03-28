<?php

/**
 * Factory for Model\Network\DeviceManager
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

namespace Model\Service\Network;

/**
 * Factory for Model\Network\DeviceManager
 */
class DeviceManagerFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        return new \Model\Network\DeviceManager(
            $container->get('Database\Table\NetworkDeviceTypes'),
            $container->get('Database\Table\NetworkDevicesScanned'),
            $container->get('Database\Table\NetworkDevicesIdentified')
        );
    }
}
