<?php
/**
 * Tests for AuthenticationService
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

namespace Model\Test\Operator;

/**
 * Tests for AuthenticationService
 */
class AuthenticationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * An AuthenticationService instance pulled in by setUp()
     *
     * @var \Model\Operator\AuthenticationService
     */
    protected $_auth;

    /**
     * @ignore
     */
    protected function setUp()
    {
        // Create table and default account
        \Library\Application::getService('Database\Table\Operators')->setSchema();

        $this->_auth = new \Model\Operator\AuthenticationService;
        $this->_auth->setServiceLocator(\Library\Application::getService('ServiceManager'));
    }

    /**
     * Test service retrieval
     */
    public function testService()
    {
        $this->assertInstanceOf(
            'Model\Operator\AuthenticationService',
            \Library\Application::getService('Zend\Authentication\AuthenticationService')
        );
    }

    /**
     * Test login() method with valid and invalid credentials
     */
    public function testLogin()
    {
        $this->assertFalse($this->_auth->login('', 'admin')); // Should not throw exception
        $this->assertFalse($this->_auth->login('baduser', 'admin'));
        $this->assertFalse($this->_auth->login('admin', 'badpassword'));

        $this->assertTrue($this->_auth->login('admin', 'admin'));
        $this->assertEquals('admin', $this->_auth->getIdentity());

        // clean up
        $this->_auth->clearIdentity();
    }

    public function testChangeIdentityValid()
    {
        // Get valid identity first
        $this->_auth->login('admin', 'admin');
        $this->_auth->changeIdentity('test');
        $this->assertEquals('test', $this->_auth->getIdentity());
    }

    public function testChangeIdentityUnauthenticated()
    {
        $this->setExpectedException('LogicException');
        $this->_auth->changeIdentity('test');
    }

    public function testChangeIdentityNoIdentity()
    {
        $this->setExpectedException('InvalidArgumentException', 'No identity provided');
        $this->_auth->changeIdentity('');
    }
}
