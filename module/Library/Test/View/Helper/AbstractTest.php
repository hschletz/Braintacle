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
     * Get the application's configured view helper manager
     * 
     * @return \Zend\View\HelperPluginManager
     */
    protected function _getHelperManager()
    {
        return Application::getService('ViewHelperManager');
    }

    /**
     * Get an initialized instance of the view helper
     * 
     * @param bool $setView Initialize the helper with a working view renderer (default: TRUE)
     * @return \Zend\View\Helper\HelperInterface Helper instance
     */
    protected function _getHelper($setView=true)
    {
        $helper = $this->_getHelperManager()->get($this->_getHelperName());
        if ($setView) {
            $helper->setView(Application::getService('ViewManager')->getRenderer());
        }
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
        $this->assertInstanceOf('Zend\View\Helper\HelperInterface', $this->_getHelper(false));
    }
}
