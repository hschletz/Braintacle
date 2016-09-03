<?php
/**
 * Factory for ClientController
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Service;

/**
 * Factory for ClientController
 */
class ClientControllerFactory implements \Zend\ServiceManager\Factory\FactoryInterface
{
    /**
     * @internal
     */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        return new \Console\Controller\ClientController(
            $container->get('Model\Client\ClientManager'),
            $container->get('Model\Group\GroupManager'),
            $container->get('Model\Registry\RegistryManager'),
            $container->get('Model\SoftwareManager'),
            $container->get('FormElementManager'),
            $container->get('Model\Config'),
            $container->get('Library\InventoryUploader')
        );
    }
}
