<?php
/**
 * Braintacle command line tools collection
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

namespace Tools;

use Zend\ModuleManager\Feature;

/**
 * Braintacle command line tools collection
 */
class Module implements
    Feature\InitProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\AutoloaderProviderInterface
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
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
        $config = array(
            'service_manager' => array(
                'factories' => array(
                    'Tools\Controller\Apidoc' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Tools\Controller\Build' => function ($container) {
                        return new Controller\Build(
                            $container->get('Model\Config'),
                            $container->get('Model\Package\PackageManager')
                        );
                    },
                    'Tools\Controller\Database' => function ($container) {
                        return new Controller\Database(
                            $container->get('Database\SchemaManager'),
                            $container->get('Library\Logger'),
                            $container->get('Library\Log\Writer\StdErr')
                        );
                    },
                    'Tools\Controller\Decode' => function ($container) {
                        return new Controller\Decode(
                            $container->get('FilterManager')->get('Protocol\InventoryDecode')
                        );
                    },
                    'Tools\Controller\Export' => function ($container) {
                        return new Controller\Export(
                            $container->get('Model\Client\ClientManager')
                        );
                    },
                    'Tools\Controller\Import' => function ($container) {
                        return new Controller\Import(
                            $container->get('Model\Client\ClientManager')
                        );
                    },
                ),
            ),
            'tool_routes' => array(
                array(
                    'name' => 'apidoc',
                    'short_description' => 'Generate API documentation in the doc/api directory',
                    'handler' => 'Tools\Controller\Apidoc',
                ),
                array(
                    'name' => 'build',
                    'route' => '<name> <file>',
                    'short_description' => 'Build a package',
                    'options_descriptions' => array(
                        '<name>' => 'package name',
                        '<file>' => 'file with package content',
                    ),
                    'handler' => 'Tools\Controller\Build',
                ),
                array(
                    'name' => 'database',
                    'route' => '[--loglevel=] [--prune|-p]',
                    'short_description' => 'Update the database',
                    'options_descriptions' => array(
                        '--loglevel=emerg|alert|crit|err|warn|notice|info|debug' => 'maximum log level, default: info',
                        '--prune|-p' => 'Drop obsolete tables and columns (default: just warn)',
                    ),
                    'constraints' => array(
                        'loglevel' => '/^(emerg|alert|crit|err|warn|notice|info|debug)$/',
                    ),
                    'filters' => array(
                        'loglevel' => 'Library\Filter\LogLevel',
                    ),
                    'handler' => 'Tools\Controller\Database',
                ),
                array(
                    'name' => 'decode',
                    'route' => '<input_file> [<output_file>]',
                    'short_description' => 'Decode a compressed inventory file as created by agents',
                    'options_descriptions' => array(
                        '<input_file>' => 'compressed input file',
                        '<output_file>' => 'XML output file (default: print to STDOUT)',
                    ),
                    'handler' => 'Tools\Controller\Decode',
                ),
                array(
                    'name' => 'export',
                    'route' => '[--validate|-v] <directory>',
                    'short_description' => 'Export all clients as XML',
                    'options_descriptions' => array(
                        '<directory>' => 'output directory',
                        '--validate|-v' => 'validate output documents, abort on error',
                    ),
                    'handler' => 'Tools\Controller\Export',
                ),
                array(
                    'name' => 'import',
                    'route' => '<filename>',
                    'short_description' => 'Import clients from compressed or uncompressed XML files',
                    'handler' => 'Tools\Controller\Import',
                ),
            )
        );
        // Add common options for all routes
        foreach ($config['tool_routes'] as &$route) {
            // Options may not be present for all routes
            $route['route'] = '[--config=] ' . @$route['route'];
            $route['options_descriptions'] = array_merge(
                array('--config' => 'Alternative config file'),
                isset($route['options_descriptions']) ? $route['options_descriptions'] : array()
            );
        }
        return $config;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
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
}
