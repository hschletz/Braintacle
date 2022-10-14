<?php

/**
 * Base class for controller plugin tests
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

namespace Library\Test\Mvc\Controller\Plugin;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\PluginInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;

/**
 * Base class for controller plugin tests
 *
 * Tests for controller plugin classes can derive from this class for some
 * convenience functions. Additionally, the testPluginInterface() test is
 * executed for all derived tests.
 */
abstract class AbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Service manager
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected static $_serviceManager;

    /**
     * Controller used for tests, if set by _getPlugin()
     * @var \Laminas\Stdlib\DispatchableInterface
     */
    protected $_controller;

    public static function setUpBeforeClass(): void
    {
        $module = strtok(get_called_class(), '\\');
        $application = \Library\Application::init($module);
        static::$_serviceManager = $application->getServiceManager();
        static::$_serviceManager->setService(
            'Library\UserConfig',
            array(
                'debug' => array(
                    'display backtrace' => true,
                    'report missing translations' => true,
                ),
            )
        );
    }

    /**
     * Get the name of the controller plugin, derived from the test class name
     *
     * @return string Plugin name
     */
    protected function getPluginName()
    {
        // Derive plugin name from test class name (minus namespace and 'Test' suffix)
        return substr(strrchr(get_class($this), '\\'), 1, -4);
    }

    /**
     * Get the application's configured controller plugin manager
     *
     * @return \Laminas\Mvc\Controller\PluginManager
     */
    protected function getPluginManager()
    {
        return static::$_serviceManager->get('ControllerPluginManager');
    }

    /**
     * Get an initialized instance of the controller plugin
     *
     * The controller will be a \Laminas\Mvc\Controller\AbstractActionController
     * mock. Its MvcEvent will be initialized with a standard route 'test'
     * (/module/controller/action/) with defaults of "defaultcontroller" and
     * "defaultaction". An empty response is created.
     */
    protected function getPlugin(): callable
    {
        $router = new TreeRouteStack();
        $router->addRoute(
            'test',
            Segment::factory([
                // Match "module" prefix, followed by controller and action
                // names. All three components are optional except the
                // controller, which is required if an action is given. Matches
                // with or without trailing slash.
                'route' => '/[module[/]][:controller[/][:action[/]]]',
                'defaults' => [
                    'controller' => 'defaultcontroller',
                    'action' => 'defaultaction',
                ],
            ])
        );

        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('test');

        $event = new MvcEvent();
        $event->setRouter($router);
        $event->setRouteMatch($routeMatch);
        $event->setResponse(new Response());

        $this->_controller = $this->getMockForAbstractClass(AbstractActionController::class);
        $this->_controller->setPluginManager($this->getPluginManager());
        $this->_controller->setEvent($event);

        return $this->_controller->plugin($this->getPluginName());
    }

    /**
     * Test if the plugin is properly registered with the service manager
     */
    public function testPluginInterface()
    {
        $class = substr(str_replace('\Test', '', get_called_class()), 0, -4);
        // Uppercase
        $this->assertInstanceOf($class, $this->getPluginManager()->get($this->getPluginName()));
        // Lowercase
        $this->assertInstanceOf($class, $this->getPluginManager()->get(lcfirst($this->getPluginName())));
    }
}
