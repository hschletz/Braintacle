<?php
/**
 * Tests for the main menu
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

namespace Console\Test\Navigation;

use Library\Application;

/**
 * Tests for the main menu
 */
class MainMenuTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    /**
     * Set up application config
     */
    public function setUp()
    {
        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getService('ApplicationConfig'));
        parent::setUp();
    }

    /**
     * Test for valid factory output
     */
    public function testMainMenuFactory()
    {
        $this->assertInstanceOf(
            'Zend\Navigation\Navigation',
            Application::getService('Console\Navigation\MainMenu')
        );
    }

    /**
     * Test for highlighting of active menu entry
     */
    public function testActive()
    {
        // Mock AuthenticationService to provide an identity
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $auth->expects($this->any())
             ->method('hasIdentity')
             ->will($this->returnValue(true));
        $auth->expects($this->any())
             ->method('getIdentity')
             ->will($this->returnValue('test'));

        // Mock model to make action run without errors
        $model = $this->getMock('Model_Windows');
        $model->expects($this->any())
              ->method('getNumManualProductKeys')
              ->will($this->returnValue(0));

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Library\AuthenticationService', $auth)
             ->setService('Model\Computer\Windows', $model);

        // Dispatch arbitrary action and test corresponding menu entry
        $this->dispatch('/console/licenses/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('li.active a', 'Licenses');
    }
}
