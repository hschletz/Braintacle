<?php

/**
 * Tests for LoginController
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

namespace Console\Test\Controller;

use Console\Form\Login;
use Model\Operator\AuthenticationService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for LoginController
 */
class LoginControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * @var MockObject|AuthenticationService
     */
    protected $_authenticationService;

    /**
     * @var MockObject|Login
     */
    protected $_form;

    /** {@inheritdoc} */
    public function setUp(): void
    {
        parent::setUp();

        $this->_authenticationService = $this->createMock('\Model\Operator\AuthenticationService');
        $this->_form = $this->createMock('Console\Form\Login');

        $serviceLocator = $this->getApplicationServiceLocator();

        // Call method on overridden service to satisfy atLeastOnce constraint
        $serviceLocator->get('Model\Operator\AuthenticationService')->hasIdentity();

        $serviceLocator->setAllowOverride(true);
        $serviceLocator->setService('Model\Operator\AuthenticationService', $this->_authenticationService);
        $serviceLocator->get('FormElementManager')->setService('Console\Form\Login', $this->_form);
    }

    public function testRedirectToLoginPage()
    {
        // Skip Test for Login controller
    }

    /**
     * Set up mock AuthenticationService
     *
     * @param bool $hasIdentity Return value for the hasIdentity() stub
     */
    protected function mockAuthenticationService($hasIdentity)
    {
        $this->_authenticationService->expects($this->any())
                                     ->method('login')
                                     ->will(
                                         $this->returnValueMap(
                                             array(
                                                 array('gooduser', 'goodpassword', true),
                                                 array('baduser', 'badpassword', false),
                                             )
                                         )
                                     );
        $this->_authenticationService->expects($this->any())
                                     ->method('hasIdentity')
                                     ->will($this->returnValue($hasIdentity));
    }

    public function testLoginActionWithIdentity()
    {
        $this->mockAuthenticationService(true);
        $this->dispatch('/console/login/login');
        $this->assertRedirectTo('/console/client/index/');
    }

    public function testLoginActionWithoutIdentityPostValidCorrectCredentials()
    {
        $this->mockAuthenticationService(false);
        $postData = array('userid' => 'gooduser', 'password' => 'goodpassword');
        $this->_form->expects($this->once())
                    ->method('setData')
                    ->with($postData);
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->will($this->returnValue(true));
        $this->_form->expects($this->once())
                    ->method('getData')
                    ->will($this->returnValue(array('User' => 'gooduser', 'Password' => 'goodpassword')));
        $this->dispatch('/console/login/login', 'POST', $postData);
        $this->assertRedirectTo('/console/client/index/');
    }

    public function testLoginActionWithoutIdentityPostValidIncorrectCredentials()
    {
        $this->mockAuthenticationService(false);
        $postData = array('userid' => 'baduser', 'password' => 'badpassword');
        $this->_form->expects($this->once())
                    ->method('setData')
                    ->with($postData);
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->will($this->returnValue(true));
        $this->_form->expects($this->once())
                    ->method('getData')
                    ->will($this->returnValue(array('User' => 'baduser', 'Password' => 'badpassword')));
        $this->_form->expects($this->once())
                    ->method('render');
        $this->dispatch('/console/login/login', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryContentContains('//p[@class="error"]', "\nBenutzername und/oder Passwort ungültig.\n");
    }

    public function testLoginActionWithoutIdentityPostInvalid()
    {
        $this->mockAuthenticationService(false);
        $postData = array('userid' => '', 'password' => '');
        $this->_form->expects($this->once())
                    ->method('setData')
                    ->with($postData);
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->will($this->returnValue(false));
        $this->_form->expects($this->once())
                    ->method('render');
        $this->dispatch('/console/login/login', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryContentContains('//p[@class="error"]', "\nBenutzername und/oder Passwort ungültig.\n");
    }

    public function testLoginActionWithoutIdentityGet()
    {
        $this->mockAuthenticationService(false);
        $this->_form->expects($this->never())
                    ->method('setData');
        $this->_form->expects($this->never())
                    ->method('isValid');
        $this->_form->expects($this->once())
                    ->method('render')
                    ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/login/login');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
        $this->assertNotXPathQuery('//p[@class="error"]');
    }

    public function testLoginActionRedidectsToPreviousPageAfterSuccessfulLogin()
    {
        $this->mockAuthenticationService(false);
        $postData = array('userid' => 'gooduser', 'password' => 'goodpassword');
        $session = new \Laminas\Session\Container('login');
        $session->originalUri = 'redirectTest';
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->will($this->returnValue(true));
        $this->_form->expects($this->once())
                    ->method('getData')
                    ->will($this->returnValue(array('User' => 'gooduser', 'Password' => 'goodpassword')));
        $this->dispatch('/console/login/login', 'POST', $postData);
        $this->assertRedirectTo('redirectTest');
        $this->assertArrayNotHasKey('login', $_SESSION); // Should be cleared by action
    }

    public function testIndexAction()
    {
        $this->dispatch('/console/login/index/');
        $this->assertRedirectTo('/console/login/login/');
    }

    public function testLogoutAction()
    {
        $this->mockAuthenticationService(true);
        $this->_authenticationService->expects($this->once())->method('clearIdentity');
        $this->dispatch('/console/login/logout');
        $this->assertRedirectTo('/console/login/login/');
    }
}
