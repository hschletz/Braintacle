<?php
/**
 * Tests for LoginController
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

namespace Console\Test\Controller;

/**
 * Tests for LoginController
 */
class LoginControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Common setup for loginAction tests
     *
     * This is called before every test for loginAction(). It replaces
     * Library\AuthenticationService with a stub (credentials for login():
     * gooduser/goodpassword and baduser/badpassword). It also calls reset().

     * @param bool $hasIdentity Return value for the hasIdentity() stub
     */
    protected function _setupLoginActionTest($hasIdentity)
    {
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $auth->expects($this->any())
             ->method('login')
             ->will(
                 $this->returnValueMap(
                     array(
                        array('gooduser', 'goodpassword', true),
                        array('baduser', 'badpassword', false),
                    )
                 )
             );
        $auth->expects($this->any())
             ->method('hasIdentity')
             ->will($this->returnValue($hasIdentity));

        $this->reset();
        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Library\AuthenticationService', $auth);
    }

    /**
     * Common tests for indexAction() and loginAction()
     *
     * @param string $uri Uri to dispatch
     */
    protected function _testLoginAction($uri)
    {
        // Requests to authenticated session should yield redirect.
        $this->_setupLoginActionTest(true);
        $this->dispatch($uri);
        $this->assertRedirectTo('/console/computer/index/');

        // GET request should yield login form.
        $this->_setupLoginActionTest(false);
        $this->dispatch($uri);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form.form_login');

        // POST request with invalid form data should yield login form.
        $this->_setupLoginActionTest(false);
        $this->dispatch($uri, 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form.form_login');

        // POST request with invalid credentials should yield login form.
        $this->_setupLoginActionTest(false);
        $this->dispatch(
            $uri,
            'POST',
            array('userid' => 'baduser', 'password' => 'badpassword')
        );
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form.form_login');

        // POST request with valid credentials should yield redirect.
        $this->_setupLoginActionTest(false);
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
        $this->_testLoginAction('/console/login/index');
    }

    /**
     * Tests for loginAction()
     */
    public function testLoginAction()
    {
        $this->_testLoginAction('/console/login/login');
    }

    /**
     * Tests for logoutAction()
     */
    public function testLogoutAction()
    {
        $this->dispatch('/console/login/logout');
        $this->assertRedirectTo('/console/login/login/');
    }
}
