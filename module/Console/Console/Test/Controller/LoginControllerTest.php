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

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\LoginController($this->_authenticationService);
    }

    /**
     * Set up mock AuthenticationService
     *
     * @param bool $hasIdentity Return value for the hasIdentity() stub
     */
    protected function _mockAuthenticationService($hasIdentity)
    {
        $this->_authenticationService = $this->getMock('Library\Authentication\AuthenticationService');
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

    /**
     * Common tests for indexAction() and loginAction() with supplied identity
     *
     * @param string $uri Uri to dispatch
     */
    protected function _testLoginActionWithIdentity($uri)
    {
        // Requests to authenticated session should yield redirect.
        $this->_mockAuthenticationService(true);
        $this->dispatch($uri);
        $this->assertRedirectTo('/console/computer/index/');
    }


    /**
     * Common tests for indexAction() and loginAction() without supplied identity
     *
     * @param string $uri Uri to dispatch
     */
    protected function _testLoginActionWithoutIdentity($uri)
    {
        $this->_mockAuthenticationService(false);

        // GET request should yield login form.
        $this->dispatch($uri);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form.form_login');

        // POST request with invalid form data should yield login form.
        $this->dispatch($uri, 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form.form_login');

        // POST request with invalid credentials should yield login form.
        $this->dispatch(
            $uri,
            'POST',
            array('userid' => 'baduser', 'password' => 'badpassword')
        );
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form.form_login');

        // POST request with valid credentials should yield redirect.
        $this->dispatch(
            $uri,
            'POST',
            array('userid' => 'gooduser', 'password' => 'goodpassword')
        );
        $this->assertRedirectTo('/console/computer/index/');
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $this->_testLoginActionWithIdentity('/console/login/index');
        $this->_testLoginActionWithoutIdentity('/console/login/index');
    }

    /**
     * Tests for loginAction()
     */
    public function testLoginAction()
    {
        $this->_testLoginActionWithIdentity('/console/login/login');
        $this->_testLoginActionWithoutIdentity('/console/login/login');
    }

    /**
     * Tests for logoutAction()
     */
    public function testLogoutAction()
    {
        $this->_mockAuthenticationService(true);
        $this->dispatch('/console/login/logout');
        $this->assertRedirectTo('/console/login/login/');
    }
}
