<?php
/**
 * Tests for the module's router
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

namespace Console\Test;

/**
 * Tests for the module's router
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test route matches against various URIs
     */
    public function testRouter()
    {
        $router = \Library\Application::getService('HttpRouter');
        $request = new \Zend\Http\Request;

        $matchDefaultDefault = array(
            'controller' => 'client',
            'action' => 'index',
        );
        $matchControllerDefault = array(
            'controller' => 'controllername',
            'action' => 'index',
        );
        $matchControllerAction = array(
            'controller' => 'controllername',
            'action' => 'actionname',
        );

        $request->setUri('/');
        $this->assertEquals($matchDefaultDefault, $router->match($request)->getParams());

        $request->setUri('/controllername');
        $this->assertEquals($matchControllerDefault, $router->match($request)->getParams());

        $request->setUri('/controllername/');
        $this->assertEquals($matchControllerDefault, $router->match($request)->getParams());

        $request->setUri('/controllername/actionname');
        $this->assertEquals($matchControllerAction, $router->match($request)->getParams());

        $request->setUri('/controllername/actionname/');
        $this->assertEquals($matchControllerAction, $router->match($request)->getParams());

        $request->setUri('/controllername/actionname/invalid');
        $this->assertNull($router->match($request));

        $request->setUri('/console');
        $this->assertEquals($matchDefaultDefault, $router->match($request)->getParams());

        $request->setUri('/console/');
        $this->assertEquals($matchDefaultDefault, $router->match($request)->getParams());

        $request->setUri('/console/controllername');
        $this->assertEquals($matchControllerDefault, $router->match($request)->getParams());

        $request->setUri('/console/controllername/');
        $this->assertEquals($matchControllerDefault, $router->match($request)->getParams());

        $request->setUri('/console/controllername/actionname');
        $this->assertEquals($matchControllerAction, $router->match($request)->getParams());

        $request->setUri('/console/controllername/actionname/');
        $this->assertEquals($matchControllerAction, $router->match($request)->getParams());

        $request->setUri('/console/controllername/actionname/invalid');
        $this->assertNull($router->match($request));
    }
}