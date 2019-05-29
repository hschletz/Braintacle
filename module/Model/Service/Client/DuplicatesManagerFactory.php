<?php
/**
 * Factory for Model\Client\DuplicatesManager
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Service\Client;

/**
 * Factory for Model\Client\DuplicatesManager
 */
class DuplicatesManagerFactory implements \Zend\ServiceManager\Factory\FactoryInterface
{
    /** {@inheritdoc} */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        return new \Model\Client\DuplicatesManager(
            $container->get('Database\Table\Clients'),
            $container->get('Database\Table\NetworkInterfaces'),
            $container->get('Database\Table\DuplicateAssetTags'),
            $container->get('Database\Table\DuplicateSerials'),
            $container->get('Database\Table\DuplicateMacAddresses'),
            $container->get('Database\Table\ClientConfig'),
            $container->get('Model\Client\ClientManager'),
            $container->get('Model\SoftwareManager')
        );
    }
}
