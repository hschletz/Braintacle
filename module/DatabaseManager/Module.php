<?php
/**
 * The database manager CLI application
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

namespace DatabaseManager;

use Zend\ModuleManager\Feature;

/**
 * The database manager CLI application
 */
class Module implements
    Feature\InitProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\AutoloaderProviderInterface,
    Feature\BootstrapListenerInterface,
    Feature\ConsoleUsageProviderInterface
{
    /**
     * @internal
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Database');
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
                        'schemaManager' => array(
                            'options' => array(
                                'route' => '[--loglevel=]',
                                'defaults' => array(
                                    'controller' => 'DatabaseManager\Controller',
                                    'action'     => 'schemaManager'
                                )
                            )
                        )
                    )
                )
            ),
            'controllers' => array(
                'factories' => array(
                    'DatabaseManager\Controller' => function ($serviceLocator) {
                        $serviceManager = $serviceLocator->getServiceLocator();
                        return new Controller(
                            $serviceManager->get('Database\SchemaManager'),
                            $serviceManager->get('Library\Logger')
                        );
                    }
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
    public function onBootstrap(\Zend\EventManager\EventInterface $e)
    {
        $e->getTarget()->getEventManager()->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, array($this, 'onRoute'));
    }

    /**
     * @internal
     */
    public function onRoute(\Zend\EventManager\EventInterface $e)
    {
        // Validate loglevel value. Invalid content will cause the route to fail
        // and trigger the usage message.
        if (!preg_match(
            '/^(emerg|alert|crit|err|warn|notice|info|debug)?$/',
            $e->getRouteMatch()->getParam('loglevel')
        )) {
            $e->setError(\Zend\Mvc\Application::ERROR_ROUTER_NO_MATCH);
            $e->getTarget()->getEventManager()->trigger(\Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, $e);
        }
    }

    /**
     * @internal
     */
    public function getConsoleUsage(\Zend\Console\Adapter\AdapterInterface $console)
    {
        return array(
            '[--loglevel=emerg|alert|crit|err|warn|notice|info|debug]' => 'Update the database',
            array('--loglevel', 'Set maximum log level, default: info'),
        );
    }
}
