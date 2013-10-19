<?php
/**
 * Base class for view helper tests
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

namespace Library\Test\View\Helper;

use Library\Application;

/**
 * Base class for view helper tests
 *
 * Tests for view helper classes can derive from this class for some convenience
 * functions. Additionally, the testHelperInterface() test is executed for all
 * derived tests. 
 */
abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get the name of the view helper, derived from the test class name
     * 
     * @return string Helper name
     */
    protected function _getHelperName()
    {
        // Derive helper name from test class name (minus namespace and 'Test' suffix)
        return substr(strrchr(get_class($this), '\\'), 1, -4);
    }

    /**
     * Get the name of the view helper class, derived from the test class name
     * 
     * @return string Helper name
     */
    protected function _getHelperClass()
    {
        // Derive helper class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_class($this)), 0, -4);
    }

    /**
     * Get view helper manager
     *
     * If the $helpers array is not empty, a new helper manager is created and
     * populated with the contained services, allowing easy injection of mock
     * objects. An instance of the tested helper class (derived from the test
     * class name) is also provided. By default, the standard helper manager is
     * returned.
     *
     * @param \Zend\View\Helper\HelperInterface[] Optional associative array of helper names and instances
     * @return \Zend\View\HelperPluginManager
     */
    protected function _getHelperManager(array $helpers=array())
    {
        if (empty($helpers)) {
            $helperManager = Application::getService('ViewHelperManager');
        } else {
            $helperManager = new \Zend\View\HelperPluginManager;
            foreach ($helpers as $name => $helper) {
                $helperManager->setService($name, $helper);
            }
            $name = $this->_getHelperName();
            if (!isset($helpers[$name])) {
                $helperClass = $this->_getHelperClass();
                $helperManager->setService($this->_getHelperName(), new $helperClass);
            }
        }
        return $helperManager;
    }

    /**
     * Get helper (derived from test class name) and initialize it with a view
     *
     * @param \Zend\View\Helper\HelperInterface[] Optional dependencies passed to _getHelperManager()
     * @return \Zend\View\Helper\HelperInterface Helper instance
     */
    protected function _getHelper(array $helpers=array())
    {
        return $this->_getHelperByName($this->_getHelperName(), $helpers);
    }

    /**
     * Get an arbitrary helper and initialize it with a view
     *
     * @param string $name Helper name
     * @param \Zend\View\Helper\HelperInterface[] Optional dependencies passed to _getHelperManager()
     * @return \Zend\View\Helper\HelperInterface
     */
    protected function _getHelperByName($name, array $helpers=array())
    {
        $helperManager = $this->_getHelperManager($helpers);
        if (empty($helpers)) {
            $view = $helperManager->getServiceLocator()->get('ViewManager')->getRenderer();
        } else {
            $view = new \Zend\View\Renderer\PhpRenderer;
            $view->setHelperPluginManager($helperManager);
        }
        $helper = $helperManager->get($name);
        $helper->setView($view);
        return $helper;
    }

    /**
     * Test if the helper is properly registered with the service manager
     */
    public function testHelperInterface()
    {
        // Test if the helper is registered with the application's service manager
        $this->assertTrue($this->_getHelperManager()->has($this->_getHelperName()));

        // Get helper instance through service manager and test for required interface
        $this->assertInstanceOf('Zend\View\Helper\HelperInterface', $this->_getHelper());
    }
}
