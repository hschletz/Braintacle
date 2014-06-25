<?php
/**
 * Tests for AccountsController
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
 * Tests for AccountsController
 */
class AccountsControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Operator mock
     * @var \Model_Account
     */
    protected $_operators;

    /**
     * Account creation form mock
     * @var \Form_Account_New
     */
    protected $_formAccountNew;

    /**
     * Account editing form mock
     * @var \Form_Account_Edit
     */
    protected $_formAccountEdit;

    /** {@inheritdoc} */
    public function setUp()
    {
        $this->_operators = $this->getMockBuilder('Model_Account')->disableOriginalConstructor()->getMock();
        $this->_formAccountNew = $this->getMock('Form_Account_New');
        $this->_formAccountEdit = $this->getMock('Form_Account_Edit');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\AccountsController(
            $this->_operators,
            $this->_formAccountNew,
            $this->_formAccountEdit
        );
    }

    public function testIndexActionCurrentAccount()
    {
        $account = array(
            'Id' => 'testId',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
        );
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $auth->expects($this->once())
             ->method('getIdentity')
             ->will($this->returnValue('testId'));
        $this->_operators->expects($this->once())
                         ->method('getAuthService')
                         ->will($this->returnValue($auth));
        $this->_operators->expects($this->once())
                         ->method('fetchAll')
                         ->will($this->returnValue(array($account)));

        $this->dispatch('/console/accounts/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotQuery('td a[href*="mailto:test"]');
        $this->assertNotQueryContentContains('td a', 'Delete');
        $this->assertQueryContentContains('td a[href="/console/accounts/edit/?id=testId"]', 'Edit');

    }

    public function testIndexActionOtherAccount()
    {
        $account = array(
            'Id' => 'testId',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
        );
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $auth->expects($this->once())
             ->method('getIdentity')
             ->will($this->returnValue('otherId'));
        $this->_operators->expects($this->once())
                         ->method('getAuthService')
                         ->will($this->returnValue($auth));
        $this->_operators->expects($this->once())
                         ->method('fetchAll')
                         ->will($this->returnValue(array($account)));

        $this->dispatch('/console/accounts/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td a[href="/console/accounts/delete/?id=testId"]', 'Delete');
    }

    public function testIndexActionMailAddress()
    {
        $account = array(
            'Id' => 'testId',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => 'test@example.com',
            'Comment' => '',
        );
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $auth->expects($this->once())
             ->method('getIdentity')
             ->will($this->returnValue('otherId'));
        $this->_operators->expects($this->once())
                         ->method('getAuthService')
                         ->will($this->returnValue($auth));
        $this->_operators->expects($this->once())
                         ->method('fetchAll')
                         ->will($this->returnValue(array($account)));

        $this->dispatch('/console/accounts/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td a[href="mailto:test%40example.com"]', 'test@example.com');
    }

    public function testAddActionPostValid()
    {
        $data = array(
            'Id' => 'testId',
            'Password' => 'topsecret',
            'PasswordRepeat' => 'topsecret',
        );
        $this->_operators->expects($this->once())
                         ->method('create')
                         ->with($data, 'topsecret');
        $this->_formAccountNew->expects($this->once())
                              ->method('isValid')
                              ->with($data)
                              ->will($this->returnValue(true));
        $this->_formAccountNew->expects($this->once())
                              ->method('getValues')
                              ->will($this->returnValue($data));
        $this->dispatch('/console/accounts/add/', 'POST', $data);
        $this->assertRedirectTo('/console/accounts/index/');
    }

    public function testAddActionPostInvalid()
    {
        $this->_operators->expects($this->never())
                         ->method('create');
        $this->_formAccountNew->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(false));
        $this->_formAccountNew->expects($this->once())
                              ->method('__toString')
                              ->will($this->returnValue(''));
        $this->dispatch('/console/accounts/add/', 'POST');
        $this->assertResponseStatusCode(200);
    }

    public function testAddActionGet()
    {
        $this->_operators->expects($this->never())
                         ->method('create');
        $this->_formAccountNew->expects($this->once())
                              ->method('__toString')
                              ->will($this->returnValue(''));
        $this->dispatch('/console/accounts/add/');
        $this->assertResponseStatusCode(200);
    }

    public function testEditActionGet()
    {
        $this->_formAccountEdit->expects($this->once())
                               ->method('setId')
                               ->with('testId');
        $this->_formAccountEdit->expects($this->once())
                               ->method('__toString')
                               ->will($this->returnValue('<form></form>'));
        $this->_operators->expects($this->never())
                         ->method('update');
        $this->dispatch('/console/accounts/edit/?id=testId');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
    }

    public function testEditActionPostInvalid()
    {
        $this->_formAccountEdit->expects($this->once())
                               ->method('setId')
                               ->with('testId');
        $this->_formAccountEdit->expects($this->once())
                               ->method('__toString')
                               ->will($this->returnValue('<form></form>'));
        $this->_operators->expects($this->never())
                         ->method('update');
        $this->dispatch('/console/accounts/edit/?id=testId', 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
    }

    public function testEditActionPostValid()
    {
        $data = array(
            'OriginalId' => 'testId',
            'Password' => 'topsecret',
            'PasswordRepeat' => 'topsecret',
        );
        $this->_operators->expects($this->once())
                         ->method('update')
                         ->with('testId', $data, 'topsecret');
        $this->_formAccountEdit->expects($this->once())
                               ->method('isValid')
                               ->with($data)
                               ->will($this->returnValue(true));
        $this->_formAccountEdit->expects($this->once())
                               ->method('getValues')
                               ->will($this->returnValue($data));
        $this->_formAccountEdit->expects($this->never())
                               ->method('__toString');
        $this->dispatch('/console/accounts/edit/?id=testId', 'POST', $data);
        $this->assertRedirectTo('/console/accounts/index/');
    }

    public function testDeleteActionGet()
    {
        $this->_operators->expects($this->never())
                         ->method('delete');
        $this->dispatch('/console/accounts/delete/?id=testId');
        $this->assertResponseStatusCode(200);
        $this->assertContains(
            'Account "testId" will be permanently deleted. Continue?',
            $this->getResponse()->getContent()
        );
        $this->assertQuery('form');
    }

    public function testDeleteActionPostNo()
    {
        $this->_operators->expects($this->never())
                         ->method('delete');
        $this->dispatch('/console/accounts/delete/?id=testId', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/accounts/index/');
    }

    public function testDeleteActionPostYes()
    {
        $this->_operators->expects($this->once())
                         ->method('delete')
                         ->with('testId');
        $this->dispatch('/console/accounts/delete/?id=testId', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/accounts/index/');
    }
}
