<?php

/**
 * Tests for the main menu
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

namespace Console\Test\Navigation;

use Library\Application;

/**
 * Tests for the main menu
 */
class MainMenuTest extends \Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    /**
     * Set up application config
     */
    public function setUp(): void
    {
        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getApplicationConfig('Console', false));

        parent::setUp();
    }

    /**
     * Test for valid factory output
     */
    public function testMainMenuFactory()
    {
        $this->assertInstanceOf(
            'Laminas\Navigation\Navigation',
            $this->getApplicationServiceLocator()->get('Console\Navigation\MainMenu')
        );
    }

    public function testActive()
    {
        // Mock AuthenticationService to provide an identity
        $auth = $this->createMock('Model\Operator\AuthenticationService');
        $auth->expects($this->any())
             ->method('hasIdentity')
             ->will($this->returnValue(true));
        $auth->expects($this->any())
             ->method('getIdentity')
             ->will($this->returnValue('test'));

        $model = $this->createMock('Model\SoftwareManager');
        $model->expects($this->any())
              ->method('getNumManualProductKeys')
              ->will($this->returnValue(0));

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Laminas\Authentication\AuthenticationService', $auth);
        $serviceManager->setService('Model\SoftwareManager', $model);
        $serviceManager->setService('Library\UserConfig', array());
        Application::addAbstractFactories($serviceManager);

        // Dispatch arbitrary action and test corresponding menu entry
        $this->dispatch('/console/licenses/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('li.active a', 'Lizenzen');
    }
}
