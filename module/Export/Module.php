<?php
/**
 * The export CLI application
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

namespace Export;

use Zend\ModuleManager\Feature;

/**
 * The export CLI application
 */
class Module implements
    Feature\InitProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\AutoloaderProviderInterface,
    Feature\ConsoleUsageProviderInterface
{
    /**
     * @internal
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Model');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        return array(
            'console' => array(
                'router' => array(
                    'routes' => array(
                        'export' => array(
                            'options' => array(
                                'route'    => '[--validate|-v] <directory>',
                                'defaults' => array(
                                    'controller' => 'Export\Controller',
                                    'action'     => 'export'
                                )
                            )
                        )
                    )
                )
            ),
            'controllers' => array(
                'invokables' => array(
                    'Export\Controller' => 'Export\Controller',
                )
            ),
        );
    }

    /**
     * @internal
     */
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
     * @internal
     */
    public function getConsoleUsage(\Zend\Console\Adapter\AdapterInterface $console)
    {
        return array(
            '[-validate|-v] <directory>' => 'Export all clients as XML',
            array('<directory>', 'output directory (required)'),
            array('-validate|-v', 'validate output documents, abort on error'),
        );
    }
}
