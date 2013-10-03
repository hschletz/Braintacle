<?php
/**
 * Abstract controller test case
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
        parent::setUp();
    }

    /**
     * Dispatch the MVC with an URL
     *
     * This extends the base implementation by automatically invoking reset()
     * and injecting the mock controller manager.
     *
     * @param string $url
     * @param string|null $method
     * @param array|null $params
     */
    public function dispatch($url, $method = null, $params = array())
    {
        $this->reset();
        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('ControllerLoader', $this->_controllerManager);
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
}
