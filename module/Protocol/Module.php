<?php
/**
 * The Protocol module
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Protocol;

use Zend\ModuleManager\Feature;

/**
 * The Protocol module
 *
 * This module deals with the XML data used in the client/server protocol.
 *
 * @codeCoverageIgnore
 */
class Module implements
    Feature\AutoloaderProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\InitProviderInterface
{
    /** {@inheritdoc} */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Database');
        $manager->loadModule('Library');
        $manager->loadModule('Model');
    }

    /** {@inheritdoc} */
    public function getConfig()
    {
        return array(
            'filters' => array(
                'aliases' => array(
                    'Protocol\InventoryDecode' => 'Protocol\Filter\InventoryDecode',
                ),
                'factories' => array(
                    'Protocol\Filter\InventoryDecode' => 'Zend\ServiceManager\Factory\InvokableFactory',
                ),
            ),
            'service_manager' => array(
                'abstract_factories' => array(
                    'Protocol\Service\AbstractHydratorFactory',
                ),
                'factories' => array(
                    'Protocol\Hydrator\ClientsBios' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Protocol\Hydrator\ClientsHardware' => 'Protocol\Service\Hydrator\ClientsHardwareFactory',
                    'Protocol\Hydrator\Filesystems' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Protocol\Hydrator\Software' => Service\Hydrator\SoftwareFactory::class,
                    'Protocol\Message\InventoryRequest' => 'Zend\ServiceManager\Factory\InvokableFactory',
                ),
            ),
        );
    }

    /** {@inheritdoc} */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * Get path to module directory
     *
     * @param string $path Optional path component that is appended to the module root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     */
    public static function getPath($path = '')
    {
        return \Library\Application::getPath('module/Protocol/' . $path);
    }
}
