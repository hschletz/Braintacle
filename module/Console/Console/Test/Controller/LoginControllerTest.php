<?php
/**
 * Tests for LoginController
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

namespace Console\Test\Controller;

/**
 * Tests for LoginController
 */
class LoginControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * AuthenticationService mock
     * @var \Library\Authentication\AuthenticationService
     */
    protected $_authenticationService;

    /**
     * Login form mock
     * @var \Form_Login
     */
    protected $_form;

    /** {@inheritdoc} */
    public function setUp()
    {
        $this->_authenticationService = $this->getMock('\Library\Authentication\AuthenticationService');
        $this->_form = $this->getMock('Form_Login');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\LoginController(
            $this->_authenticationService,
            $this->_form
        );
    }

    /**
     * Set up mock AuthenticationService
     *
     * @param bool $hasIdentity Return value for the hasIdentity() stub
     */
    protected function _mockAuthenticationService($hasIdentity)
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
        $this->_mockAuthenticationService(true);
        $this->dispatch('/console/login/login');
        $this->assertRedirectTo('/console/computer/index/');
    }

    public function testLoginActionWithoutIdentityPostValidCorrectCredentials()
    {
        $this->_mockAuthenticationService(false);
        $postData = array('userid' => 'gooduser', 'password' => 'goodpassword');
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->with($postData)
                    ->will($this->returnValue(true));
        $this->_form->expects($this->exactly(2))
                    ->method('getValue')
                    ->will(
                        $this->returnValueMap(
                            array(
                                array('userid', 'gooduser'),
                                array('password', 'goodpassword')
                            )
                        )
                    );
        $this->dispatch('/console/login/login', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/index/');
    }

    public function testLoginActionWithoutIdentityPostValidIncorrectCredentials()
    {
        $this->_mockAuthenticationService(false);
        $postData = array('userid' => 'baduser', 'password' => 'badpassword');
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->with($postData)
                    ->will($this->returnValue(true));
        $this->_form->expects($this->exactly(2))
                    ->method('getValue')
                    ->will(
                        $this->returnValueMap(
                            array(
                                array('userid', 'baduser'),
                                array('password', 'badpassword')
                            )
                        )
                    );
        $this->_form->expects($this->once())
                    ->method('toHtml');
        $this->dispatch('/console/login/login', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testLoginActionWithoutIdentityPostInvalid()
    {
        $this->_mockAuthenticationService(false);
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->will($this->returnValue(false));
        $this->_form->expects($this->once())
                    ->method('toHtml');
        $this->dispatch('/console/login/login', 'POST');
        $this->assertResponseStatusCode(200);
    }

    public function testLoginActionWithoutIdentityGet()
    {
        $this->_mockAuthenticationService(false);
        $this->_form->expects($this->once())
                    ->method('toHtml');
        $this->dispatch('/console/login/login');
        $this->assertResponseStatusCode(200);
    }

    public function testLoginActionRedidectsToPreviousPageAfterSuccessfulLogin()
    {
        $this->_mockAuthenticationService(false);
        $postData = array('userid' => 'gooduser', 'password' => 'goodpassword');
        $this->_sessionSetup = array('login' => array('originalUri' => 'redirectTest'));
        $this->_form->expects($this->once())
                    ->method('isValid')
                    ->with($postData)
                    ->will($this->returnValue(true));
        $this->_form->expects($this->exactly(2))
                    ->method('getValue')
                    ->will(
                        $this->returnValueMap(
                            array(
                                array('userid', 'gooduser'),
                                array('password', 'goodpassword')
                            )
                        )
                    );
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
        $this->_mockAuthenticationService(true);
        $this->_authenticationService->expects($this->once())->method('clearIdentity');
        $this->dispatch('/console/login/logout');
        $this->assertRedirectTo('/console/login/login/');
    }
}
