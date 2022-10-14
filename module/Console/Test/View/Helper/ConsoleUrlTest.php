<?php

/**
 * Tests for the ConsoleUrl helper
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

namespace Console\Test\View\Helper;

use Laminas\View\Helper\Url;

/**
 * Tests for the ConsoleUrl helper
 */
class ConsoleUrlTest extends \Library\Test\View\Helper\AbstractTest
{
    protected $_helper;

    public function setUp(): void
    {
        // Inject mock RouteMatch into Url helper which is used by ConsoleUrl
        $routeMatch = new \Laminas\Router\RouteMatch(
            array(
                'controller' => 'currentcontroller',
                'action' => 'currentaction',
            )
        );

        /** @var Url */
        $urlHelper = $this->getHelper('Url');
        $urlHelper->setRouteMatch($routeMatch);
        $this->_helper = new \Console\View\Helper\ConsoleUrl(null, $urlHelper);
    }

    public function testDefaultControllerAndAction()
    {
        $this->assertEquals(
            '/console/currentcontroller/currentaction/',
            $this->_helper->__invoke()
        );
    }

    public function testExplicitControllerAndAction()
    {
        $this->assertEquals(
            '/console/controller/action/',
            $this->_helper->__invoke('controller', 'action')
        );
    }

    public function testSingleParam()
    {
        $params = array('param1' => 'value1');
        $this->assertEquals(
            '/console/controller/action/?param1=value1',
            $this->_helper->__invoke('controller', 'action', $params)
        );
    }

    public function testMultipleParams()
    {
        $params = array('param1' => 'value1', 'param2' => 'value2');
        $this->assertEquals(
            '/console/controller/action/?param1=value1&param2=value2',
            $this->_helper->__invoke('controller', 'action', $params)
        );
    }

    public function testStringifiableObjectParam()
    {
        $params = array('param' => new \Library\MacAddress('00:00:5E:00:53:00'));
        $this->assertEquals(
            '/console/controller/action/?param=00:00:5E:00:53:00',
            $this->_helper->__invoke('controller', 'action', $params)
        );
    }

    public function testInheritParamsWithoutHttpRequest()
    {
        $params = array('param1' => 'value1');
        $this->assertEquals(
            '/console/controller/action/?param1=value1',
            $this->_helper->__invoke('controller', 'action', $params, true)
        );
    }

    public function testInheritRequestParams()
    {
        $requestParams = array('param1' => 'requestValue1');
        $request = new \Laminas\Http\PhpEnvironment\Request();
        $request->setQuery(new \Laminas\Stdlib\Parameters($requestParams));

        /** @var Url */
        $urlHelper = $this->getHelper('Url');
        $helper = new \Console\View\Helper\ConsoleUrl($request, $urlHelper);

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
