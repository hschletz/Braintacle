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
     * @var \Console\Form\Account\Add
     */
    protected $_formAccountAdd;

    /**
     * Account editing form mock
     * @var \Console\Form\Account\Edit
     */
    protected $_formAccountEdit;

    /** {@inheritdoc} */
    public function setUp()
    {
        $this->_operators = $this->getMockBuilder('Model_Account')->disableOriginalConstructor()->getMock();
        $this->_formAccountAdd = $this->getMock('Console\Form\Account\Add');
        $this->_formAccountEdit = $this->getMock('Console\Form\Account\Edit');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\AccountsController(
            $this->_operators,
            $this->_formAccountAdd,
            $this->_formAccountEdit
        );
    }

    public function testService()
    {
        $this->_overrideService('Console\Form\Account\Add', $this->_formAccountAdd, 'FormElementManager');
        $this->_overrideService('Console\Form\Account\Edit', $this->_formAccountEdit, 'FormElementManager');
        parent::testService();
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
        $this->_operators->expects($this->once())
                         ->method('fetchAll')
                         ->will($this->returnValue(array($account)));

        $identity = $this->getMock('Zend\View\Helper\Identity');
        $identity->expects($this->atLeastOnce())->method('__invoke')->willReturn('testId');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('Identity', $identity);

        $this->dispatch('/console/accounts/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotQuery('td a[href*="mailto:test"]');
        $this->assertNotQueryContentContains('td a', 'Löschen');
        $this->assertQueryContentContains('td a[href="/console/accounts/edit/?id=testId"]', 'Bearbeiten');
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
        $this->_operators->expects($this->once())
                         ->method('fetchAll')
                         ->will($this->returnValue(array($account)));

        $identity = $this->getMock('Zend\View\Helper\Identity');
        $identity->expects($this->atLeastOnce())->method('__invoke')->willReturn('otherId');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('Identity', $identity);

        $this->dispatch('/console/accounts/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td a[href="/console/accounts/delete/?id=testId"]', 'Löschen');
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
        $this->_formAccountAdd->expects($this->once())
                              ->method('setData')
                              ->with($data);
        $this->_formAccountAdd->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(true));
        $this->_formAccountAdd->expects($this->once())
                              ->method('getData')
                              ->will($this->returnValue($data));
        $this->_formAccountAdd->expects($this->never())
                              ->method('render');
        $this->dispatch('/console/accounts/add/', 'POST', $data);
        $this->assertRedirectTo('/console/accounts/index/');
    }

    public function testAddActionPostInvalid()
    {
        $data = array('Id' => 'testId');
        $this->_operators->expects($this->never())
                         ->method('create');
        $this->_formAccountAdd->expects($this->once())
                              ->method('setData')
                              ->with($data);
        $this->_formAccountAdd->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(false));
        $this->_formAccountAdd->expects($this->never())
                              ->method('getData');
        $this->_formAccountAdd->expects($this->once())
                              ->method('render')
                              ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/accounts/add/', 'POST', $data);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
    }

    public function testAddActionGet()
    {
        $this->_operators->expects($this->never())
                         ->method('create');
        $this->_formAccountAdd->expects($this->never())
                              ->method('setData');
        $this->_formAccountAdd->expects($this->never())
                              ->method('isValid');
        $this->_formAccountAdd->expects($this->never())
                              ->method('getData');
        $this->_formAccountAdd->expects($this->once())
                              ->method('render')
                              ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/accounts/add/');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
    }

    public function testEditActionGet()
    {
        $this->_operators->expects($this->never())
                         ->method('update');
        $this->_operators->expects($this->once())
                         ->method('fetch')
                         ->with('testId');
        $this->_operators->expects($this->once())
                         ->method('getArrayCopy')
                         ->will($this->returnValue(array('Id' => 'testId')));
        $this->_formAccountEdit->expects($this->once())
                               ->method('setData')
                               ->with(array('Id' => 'testId', 'OriginalId' => 'testId'));
        $this->_formAccountEdit->expects($this->never())
                               ->method('isValid');
        $this->_formAccountEdit->expects($this->never())
                               ->method('getData');
        $this->_formAccountEdit->expects($this->once())
                               ->method('render')
                               ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/accounts/edit/?id=testId');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
    }

    public function testEditActionPostInvalid()
    {
        $data = array('OriginalId' => 'testId');
        $this->_operators->expects($this->never())
                         ->method('update');
        $this->_formAccountEdit->expects($this->once())
                               ->method('setData')
                               ->with($data);
        $this->_formAccountEdit->expects($this->once())
                               ->method('isValid')
                               ->will($this->returnValue(false));
        $this->_formAccountEdit->expects($this->never())
                               ->method('getData');
        $this->_formAccountEdit->expects($this->once())
                               ->method('render')
                               ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/accounts/edit/?id=testId', 'POST', $data);
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
                               ->method('setData')
                               ->with($data);
        $this->_formAccountEdit->expects($this->once())
                               ->method('isValid')
                               ->will($this->returnValue(true));
        $this->_formAccountEdit->expects($this->once())
                               ->method('getData')
                               ->will($this->returnValue($data));
        $this->_formAccountEdit->expects($this->never())
                               ->method('render');
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
            "Account 'testId' wird dauerhaft gelöscht. Fortfahren?",
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
