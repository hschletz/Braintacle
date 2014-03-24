<?php
/**
 * Abstract controller test case
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test;

/**
 * Abstract controller test case
 *
 * This base class performs common setup for all coltroller tests.
 */
abstract class AbstractControllerTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    /**
     * ControllerManager mock
     * @var \Zend\Mvc\Controller\ControllerManager
     */
    protected $_controllerManager;

    /**
     * Session setup
     *
     * Tests can set this to a 2-dimensional array. The first dimension key is a
     * session namespace. Its value is an associative array of key=>value pairs
     * set up for the given namespace. \Zend\Session is set up with this data on
     * every call to dispatch().
     *
     * The content is reset by setUp(), i.e. every test starts with an empty
     * session.
     */
    protected $_sessionSetup;

    /**
     * Set up application config
     */
    public function setUp()
    {
        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getService('ApplicationConfig'));
        $this->_controllerManager = $this->getMock('Zend\Mvc\Controller\ControllerManager');
        $this->_controllerManager->expects($this->any())
                                 ->method('has')
                                 ->will($this->returnValue(true));
        $this->_controllerManager->expects($this->any())
                                 ->method('get')
                                 ->will($this->returnCallback(array($this, 'createController')));
        $this->_sessionSetup = array();
        parent::setUp();
    }

    /**
     * Get the name of the controller, derived from the test class name
     *
     * @return string Controller name
     */
    protected function _getControllerName()
    {
        // Derive controller name from test class name (minus namespace and 'ControllerTest' suffix)
        return substr(strrchr(get_class($this), '\\'), 1, -14);
    }

    /**
     * Get the name of the controller class, derived from the test class name
     * 
     * @return string Controller class name
     */
    protected function _getControllerClass()
    {
        // Derive controller class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_class($this)), 0, -4);
    }

    /**
     * Dispatch the MVC with an URL
     *
     * This extends the base implementation by automatically invoking reset()
     * and injecting the mock controller manager. Session data is initialized
     * from $_sessionSetup.
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @param bool $isXmlHttpRequest
     */
    public function dispatch($url, $method = null, $params = array(), $isXmlHttpRequest = false)
    {
        $this->reset();
        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('ControllerLoader', $this->_controllerManager);
        foreach ($this->_sessionSetup as $namespace => $data) {
            $container = new \Zend\Session\Container($namespace);
            foreach ($data as $key => $value) {
                $container->$key = $value;
            }
        }
        parent::dispatch($url, $method, $params);
    }

    /**
     * Create the controller
     *
     * @return \Zend\Stdlib\DispatchableInterface Controller instance
     */
    public function createController()
    {
        $controller = $this->_createController();
        $controller->setPluginManager($this->getApplicationServiceLocator()->get('ControllerPluginManager'));
        return $controller;
    }

    /**
     * Create controller instance
     *
     * This abstract method must be implemented by derived classes. It returns a
     * controller instance, with all controller-specific dependencies injected.
     *
     * @return \Zend\Stdlib\DispatchableInterface
     */
    abstract protected function _createController();

    /**
     * Override a service globally
     *
     * @param string $name Service name
     * @param mixed $service New service (a mock object, for example)
     * @param string $serviceLocatorName Service locator to manipulate (default: 'ServiceManager')
     */
    protected function _overrideService($name, $service, $serviceLocatorName='ServiceManager')
    {
        $serviceLocator = \Library\Application::getService($serviceLocatorName);
        $serviceLocator->setAllowOverride(true)->setService($name, $service);
    }

    /**
     * Test if the controller is properly registered with the service manager
     */
    public function testService()
    {
        $controller = \Library\Application::getService('ControllerLoader')->get($this->_getControllerName());
        $this->assertInstanceOf($this->_getControllerClass(), $controller);
    }

    /**
     * Get instance of a controller plugin
     *
     * @param string $name Plugin name
     * @return \Zend\Mvc\Controller\Plugin\PluginInterface Plugin instance
     */
    protected function _getControllerPlugin($name)
    {
        return \Library\Application::getService('ControllerPluginManager')->get($name);
    }
}
