<?php

/**
 * Braintacle command line tools collection
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

namespace Tools;

use Laminas\ModuleManager\Feature;
use Model\Client\ClientManager;
use Model\Config;
use Model\Package\PackageManager;

/**
 * Braintacle command line tools collection
 */
class Module implements
    Feature\InitProviderInterface,
    Feature\ConfigProviderInterface
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function init(\Laminas\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Database');
        $manager->loadModule('Library');
        $manager->loadModule('Model');
        $manager->loadModule('Protocol');
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getConfig()
    {
        return [
            'service_manager' => [
                'factories' => [
                    'Tools\command:build' => function ($container) {
                        return new Controller\Build(
                            $container->get(Config::class),
                            $container->get(PackageManager::class)
                        );
                    },
                    'Tools\command:database' => function ($container) {
                        return new Controller\Database(
                            $container->get('Database\SchemaManager'),
                            $container->get('Library\Logger'),
                            $container->get('Library\Log\Writer\StdErr'),
                            $container->get('FilterManager')->get('Library\LogLevel'),
                            $container->get('ValidatorManager')->get('Library\LogLevel')
                        );
                    },
                    'Tools\command:decode' => function ($container) {
                        return new Controller\Decode(
                            $container->get('FilterManager')->get('Protocol\InventoryDecode')
                        );
                    },
                    'Tools\command:export' => function ($container) {
                        return new Controller\Export($container->get(ClientManager::class));
                    },
                    'Tools\command:import' => function ($container) {
                        return new Controller\Import($container->get(ClientManager::class));
                    },
                ],
            ],
        ];
    }
}
