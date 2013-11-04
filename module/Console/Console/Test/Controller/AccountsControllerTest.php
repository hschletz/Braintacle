<?php
/**
 * Tests for AccountsController
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
    public function _createController()
    {
        if (!$this->_formAccountNew) {
            $this->_formAccountNew = new \Form_Account_New;
        }
        if (!$this->_formAccountEdit) {
            $this->_formAccountEdit = new \Form_Account_Edit;
        }
        return new \Console\Controller\AccountsController(
            $this->_operators,
            $this->_formAccountNew,
            $this->_formAccountEdit
        );
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $url = '/console/accounts/index/';

        $account = array(
            'Id' => 'testId',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
        );

        // First query uses the same identity as the mock account, which should
        // prevent the "Delete" link, and no mail address.
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $auth->expects($this->any())
             ->method('getIdentity')
             ->will($this->returnValue('testId'));

        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->_operators->expects($this->once())
                         ->method('getAuthService')
                         ->will($this->returnValue($auth));
        $this->_operators->expects($this->once())
                         ->method('fetchAll')
                         ->will($this->returnValue(array($account)));

        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertNotQuery('td a[href*="mailto:test"]');
        $this->assertNotQueryContentContains('td a', 'Delete');
        // The "Edit" link is independent of the conditions and only tested once.
        $this->assertQueryContentContains('td a[href="/console/accounts/edit/?id=testId"]', 'Edit');

        // Another query with different identity and a mail address
        $account['MailAddress'] = 'test@example.com';
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $auth->expects($this->any())
             ->method('getIdentity')
             ->will($this->returnValue('otherId'));

        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->_operators->expects($this->once())
                         ->method('getAuthService')
                         ->will($this->returnValue($auth));
        $this->_operators->expects($this->once())
                         ->method('fetchAll')
                         ->will($this->returnValue(array($account)));

        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td a[href="mailto:test%40example.com"]', 'test@example.com');
        $this->assertQueryContentContains('td a[href="/console/accounts/delete/?id=testId"]', 'Delete');
    }

    /**
     * Tests for addAction()
     */
    public function testAddAction()
    {
        $url = '/console/accounts/add/';
        $auth = $this->getMock('Library\Authentication\AuthenticationService');

        // GET request should display form
        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');

        // POST request without valid data should display form
        $this->dispatch($url, 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');

        // POST request with valid data should create account and redirect to index action
        $data = array(
            'Id' => 'testId',
            'Password' => 'topsecret',
            'PasswordRepeat' => 'topsecret',
        );
        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->_operators->expects($this->once())
                         ->method('create')
                         ->with($data, 'topsecret');
        $this->_formAccountNew = $this->getMock('Form_Account_New');
        $this->_formAccountNew->expects($this->once())
                              ->method('isValid')
                              ->with($data)
                              ->will($this->returnValue(true));
        $this->_formAccountNew->expects($this->once())
                              ->method('getValues')
                              ->will($this->returnValue($data));
        $this->dispatch($url, 'POST', $data);
        $this->assertRedirectTo('/console/accounts/index/');
    }

    /**
     * Tests for editAction()
     */
    public function testEditAction()
    {
        $url = '/console/accounts/edit/?id=testId';
        $auth = $this->getMock('Library\Authentication\AuthenticationService');
        $this->_formAccountEdit = $this->getMock('Form_Account_Edit');
        $this->_formAccountEdit->expects($this->any())
                               ->method('setId')
                               ->with('testId');
        $this->_formAccountEdit->expects($this->any())
                               ->method('__toString')
                               ->will($this->returnValue('<form></form>'));

        // GET request should display form
        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');

        // POST request without valid data should display form
        $this->dispatch($url, 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');

        // POST request with valid data should update account and redirect to index action
        $data = array(
            'OriginalId' => 'testId',
            'Password' => 'topsecret',
            'PasswordRepeat' => 'topsecret',
        );
        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->_operators->expects($this->once())
                         ->method('update')
                         ->with('testId', $data, 'topsecret');
        $this->_formAccountEdit = $this->getMock('Form_Account_Edit');
        $this->_formAccountEdit->expects($this->once())
                               ->method('isValid')
                               ->with($data)
                               ->will($this->returnValue(true));
        $this->_formAccountEdit->expects($this->once())
                               ->method('getValues')
                               ->will($this->returnValue($data));
        $this->dispatch($url, 'POST', $data);
        $this->assertRedirectTo('/console/accounts/index/');
    }

    /**
     * Tests for deleteAction()
     */
    public function testDeleteAction()
    {
        $url = '/console/accounts/delete/?id=testId';
        $auth = $this->getMock('Library\Authentication\AuthenticationService');

        // GET request should display form and caption containing Id
        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertContains(
            'Account "testId" will be permanently deleted. Continue?',
            $this->getResponse()->getContent()
        );
        $this->assertQuery('form');

        // POST request without 'yes' argument should redirect to index and not delete account
        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->_operators->expects($this->never())
                         ->method('delete');
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/accounts/index/');

        // POST request with 'yes' argument should redirect to index and delete account
        $this->_operators = $this->getMockBuilder('Model_Account')
                                 ->setConstructorArgs(array($auth))
                                 ->getMock();
        $this->_operators->expects($this->once())
                         ->method('delete')
                         ->with('testId');
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/accounts/index/');
    }
}
