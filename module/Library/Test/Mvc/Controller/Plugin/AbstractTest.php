<?php
/**
 * Base class for controller plugin tests
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

namespace Library\Test\Mvc\Controller\Plugin;

/**
 * Base class for controller plugin tests
 *
 * Tests for controller plugin classes can derive from this class for some
 * convenience functions. Additionally, the testPluginInterface() test is
 * executed for all derived tests.
 */
abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Service manager
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected static $_serviceManager;

    /**
     * Controller used for tests, if set by _getPlugin()
     * @var \Zend\Stdlib\DispatchableInterface
     */
    protected $_controller;

    public static function setUpBeforeClass()
    {
        $module = strtok(get_called_class(), '\\');
        $application = \Library\Application::init($module, true);
        static::$_serviceManager = $application->getServiceManager();
    }

    /**
     * Get the name of the controller plugin, derived from the test class name
     *
     * @return string Plugin name
     */
    protected function _getPluginName()
    {
        // Derive plugin name from test class name (minus namespace and 'Test' suffix)
        return substr(strrchr(get_class($this), '\\'), 1, -4);
    }

    /**
     * Get the application's configured controller plugin manager
     *
     * @return \Zend\Mvc\Controller\PluginManager
     */
    protected function _getPluginManager()
    {
        return static::$_serviceManager->get('ControllerPluginManager');
    }

    /**
     * Get an initialized instance of the controller plugin
     *
     * If controller setup is requested, the controller will be a
     * \Zend\Mvc\Controller\AbstractActionController mock. Its MvcEvent will be
     * initialized with a standard route 'test' (/module/controller/action/)
     * with defaults of "defaultcontroller" and "defaultaction".
     * The RouteMatch is initialized with "currentcontroller" and
     * "currentaction". An empty response is created.
     *
     * @param bool $setController Initialize the helper with a working controller (default: TRUE)
     * @return \Zend\Mvc\Controller\Plugin\PluginInterface Plugin instance
     */
    protected function _getPlugin($setController = true)
    {
        if ($setController) {
            $router = new \Zend\Router\Http\TreeRouteStack;
            $router->addRoute(
                'test',
                \Zend\Router\Http\Segment::factory(
                    array(
                        // Match "module" prefix, followed by controller and action
                        // names. All three components are optional except the
                        // controller, which is required if an action is given.
                        // Matches with or without trailing slash.
                        'route' => '/[module[/]][:controller[/][:action[/]]]',
                        'defaults' => array(
                            'controller' => 'defaultcontroller',
                            'action' => 'defaultaction',
                        ),
                    )
                )
            );

            $routeMatch = new \Zend\Router\RouteMatch(
                array(
                    'controller' => 'currentcontroller',
                    'action' => 'currentaction',
                )
            );
            $routeMatch->setMatchedRouteName('test');

            $event = new \Zend\Mvc\MvcEvent;
            $event->setRouter($router);
            $event->setRouteMatch($routeMatch);
            $event->setResponse(new \Zend\Http\Response);

            $this->_controller = $this->getMockBuilder('Zend\Mvc\Controller\AbstractActionController')
                                      ->setMethods(null)
                                      ->getMockForAbstractClass();
            $this->_controller->setPluginManager($this->_getPluginManager());
            $this->_controller->setEvent($event);

            return $this->_controller->plugin($this->_getPluginName());
        } else {
            return $this->_getPluginManager()->get($this->_getPluginName());
        }
    }

    /**
     * Test if the plugin is properly registered with the service manager
     */
    public function testPluginInterface()
    {
        $class = substr(str_replace('\Test', '', get_called_class()), 0, -4);
        // Uppercase
        $this->assertInstanceOf($class, $this->_getPluginManager()->get($this->_getPluginName()));
        // Lowercase
        $this->assertInstanceOf($class, $this->_getPluginManager()->get(lcfirst($this->_getPluginName())));
    }
}
