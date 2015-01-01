<?php
/**
 * Tests for the ConsoleUrl helper
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

namespace Console\Test\View\Helper;

/**
 * Tests for the ConsoleUrl helper
 */
class ConsoleUrlTest extends \Library\Test\View\Helper\AbstractTest
{
    /**
     * Tests for the __invoke() method
     */
    public function testInvoke()
    {
        // Inject mock RouteMatch into Url helper which is used by ConsoleUrl
        $routeMatch = new \Zend\Mvc\Router\RouteMatch(
            array(
                'controller' => 'currentcontroller',
                'action' => 'currentaction',
            )
        );
        $this->_getHelper('Url')->setRouteMatch($routeMatch);

        // Inject request parameters
        $requestParams = array('param1' => 'requestValue1');
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters($requestParams));

        $helper = new \Console\View\Helper\ConsoleUrl($request, $this->_getHelper('Url'));

        // Default is currentcontroller/currentaction
        $this->assertEquals(
            '/console/currentcontroller/currentaction/',
            $helper()
        );

        // Override controller/action
        $this->assertEquals(
            '/console/controller/action/',
            $helper('controller', 'action')
        );

        // Test with parameters
        $params = array('param1' => 'value1');
        $this->assertEquals(
            '/console/controller/action/?param1=value1',
            $helper('controller', 'action', $params)
        );

        $params['param2'] = 'value2';
        $this->assertEquals(
            '/console/controller/action/?param1=value1&param2=value2',
            $helper('controller', 'action', $params)
        );

        // Test with stringifiable object parameter
        $params = array('param' => new \Library\MacAddress('00:00:5E:00:53:00'));
        $this->assertEquals(
            '/console/controller/action/?param=00:00:5E:00:53:00',
            $helper('controller', 'action', $params)
        );

        // Test with request parameters
        $params = array();
        $this->assertEquals(
            '/console/controller/action/?param1=requestValue1',
            $helper('controller', 'action', $params, true)
        );

        $params['param2'] = 'value2';
        $this->assertEquals(
            '/console/controller/action/?param1=requestValue1&param2=value2',
            $helper('controller', 'action', $params, true)
        );

        $params['param1'] = 'value1';
        $this->assertEquals(
            '/console/controller/action/?param1=value1&param2=value2',
            $helper('controller', 'action', $params, true)
        );
    }
}
