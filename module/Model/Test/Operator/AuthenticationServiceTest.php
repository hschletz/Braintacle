<?php
/**
 * Tests for AuthenticationService
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

namespace Model\Test\Operator;

/**
 * Tests for AuthenticationService
 */
class AuthenticationServiceTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Operators');

    /**
     * An AuthenticationService instance pulled in by setUp()
     *
     * @var \Model\Operator\AuthenticationService
     */
    protected $_auth;

    protected function setUp()
    {
        parent::setUp();
        $this->_auth = clone static::$serviceManager->get('Zend\Authentication\AuthenticationService');
    }

    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet;
    }

    public function testService()
    {
        $this->assertInstanceOf('Model\Operator\AuthenticationService', $this->_auth);
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
