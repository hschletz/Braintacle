<?php

/**
 * Tests for Edit form
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

namespace Console\Test\Form\Account;

/**
 * Tests for Edit form
 */
class EditTest extends \Console\Test\AbstractFormTest
{
    protected $_operatorManager;

    public function setUp(): void
    {
        $this->_operatorManager = $this->createMock('Model\Operator\OperatorManager');
        $this->_operatorManager->method('getAllIds')->willReturn(array('User1', 'User2'));
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        $form = new \Console\Form\Account\Edit(
            null,
            array('operatorManager' => $this->_operatorManager)
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('Id'));
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->_form->get('Password'));
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->_form->get('PasswordRepeat'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('FirstName'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('LastName'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('MailAddress'));
        $this->assertInstanceOf('Laminas\Form\Element\TextArea', $this->_form->get('Comment'));
        $this->assertInstanceOf('Laminas\Form\Element\Hidden', $this->_form->get('OriginalId'));

        $submit = $this->_form->get('Submit');
        $this->assertInstanceOf('\Library\Form\Element\Submit', $submit);
        $this->assertEquals('Change', $submit->getValue());
    }

    public function testInputFilterValidMinimal()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('User1', $data['Id']);
        $this->assertEquals('User1', $data['OriginalId']);
        $this->assertSame('', $data['Password']);
        $this->assertNull($data['FirstName']);
        $this->assertNull($data['LastName']);
        $this->assertNull($data['MailAddress']);
        $this->assertNull($data['Comment']);
    }

    public function testInputFilterValidOptionalWhitespaceOnly()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '        ',
            'PasswordRepeat' => '        ',
            'FirstName' => ' ',
            'LastName' => ' ',
            'MailAddress' => ' ',
            'Comment' => ' ',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('User1', $data['Id']);
        $this->assertEquals('        ', $data['Password']);
        $this->assertNull($data['FirstName']);
        $this->assertNull($data['LastName']);
        $this->assertNull($data['MailAddress']);
        $this->assertNull($data['Comment']);
    }

    public function testInputFilterValidMaxLength()
    {
        $string = str_repeat("\xC3\x84", 255);
        $data = array(
            'Id' => " $string ",
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => " $string ",
            'LastName' => " $string ",
            'MailAddress' => '',
            'Comment' => " $string ",
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals($string, $data['Id']);
        $this->assertEquals($string, $data['FirstName']);
        $this->assertEquals($string, $data['LastName']);
        $this->assertEquals($string, $data['Comment']);
    }

    public function testInputFilterInvalidTooLong()
    {
        $string = str_repeat("\xC3\x84", 256);
        $data = array(
            'Id' => $string,
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => $string,
            'LastName' => $string,
            'MailAddress' => $string,
            'Comment' => $string, // valid
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(4, $messages);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['Id']);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['FirstName']);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['LastName']);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['MailAddress']);
    }

    public function testInputFilterInvalidUserWhitespaceOnly()
    {
        $data = array(
            'Id' => ' ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('isEmpty', $messages['Id']);
    }

    public function testInputFilterInvalidPasswordMaxLength()
    {
        $password = str_repeat('a', 72);
        $data = array(
            'Id' => ' User1 ',
            'Password' => $password,
            'PasswordRepeat' => $password,
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInvalidPasswordTooLong()
    {
        $password = str_repeat("\xC3\x84", 36) . 'a';
        $data = array(
            'Id' => ' User1 ',
            'Password' => $password,
            'PasswordRepeat' => $password,
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals(
            array(
                'stringLengthTooLong' => 'TRANSLATE(The password is longer than 72 bytes)',
            ),
            $messages['Password']
        );
    }

    public function testInputFilterInvalidPasswordTooShort()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => "\xC3\x84\xC3\x84\xC3\x84\xC3\x84\xC3\x84", // 10 bytes, but only 5 characters
            'PasswordRepeat' => "\xC3\x84\xC3\x84\xC3\x84\xC3\x84\xC3\x84",
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['Password']);
        $this->assertArrayHasKey('stringLengthTooShort', $messages['Password']);
    }

    public function testInputFilterInvalidPasswordMismatch()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '12345678',
            'PasswordRepeat' => '12345677',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['PasswordRepeat']);
        $this->assertEquals('TRANSLATE(The passwords do not match)', $messages['PasswordRepeat']['notSame']);
    }

    public function testInputFilterInvalidPasswordEmptyRepeatNonEmpty()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '',
            'PasswordRepeat' => '12345678',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['PasswordRepeat']);
        $this->assertEquals('TRANSLATE(The passwords do not match)', $messages['PasswordRepeat']['notSame']);
    }

    public function testInputFilterInvalidPasswordNonEmptyRepeatEmpty()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '12345678',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['PasswordRepeat']);
        $this->assertEquals('TRANSLATE(The passwords do not match)', $messages['PasswordRepeat']['notSame']);
    }

    public function testInputFilterInvalidEmail()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => 'invalid',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('emailAddressInvalidFormat', $messages['MailAddress']);
    }

    public function testInputFilterValidEmailDns()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => ' name@example.net ',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('name@example.net', $data['MailAddress']);
    }

    public function testInputFilterValidEmailUnregisteredTld()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => ' name@example.unregisteredtld ',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('name@example.unregisteredtld', $data['MailAddress']);
    }

    public function testInputFilterValidEmailIp()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => ' name@192.0.2.1 ',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('name@192.0.2.1', $data['MailAddress']);
    }

    public function testInputFilterValidEmailLocal()
    {
        $data = array(
            'Id' => ' User1 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => ' name@localnetworkname ',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('name@localnetworkname', $data['MailAddress']);
    }

    public function testInputFilterValidRenameNew()
    {
        $data = array(
            'Id' => ' User3 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('User3', $data['Id']);
    }

    public function testInputFilterValidRenameCaseChange()
    {
        $data = array(
            'Id' => ' user1 ',
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        $data = $this->_form->getData();
        $this->assertEquals('user1', $data['Id']);
    }

    public function testInputFilterInvalidRenameDuplicate()
    {
        $data = array(
            'Id' => ' user2 ', // Name exists
            'Password' => '',
            'PasswordRepeat' => '',
            'FirstName' => '',
            'LastName' => '',
            'MailAddress' => '',
            'Comment' => '',
            'OriginalId' => 'User1',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('Id', $messages);
    }

    public function testSetDataWithoutOriginalId()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('OriginalId not set or empty');
        $this->_form->setData(array());
    }

    public function testSetDataWithOriginalIdEmpty()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('OriginalId not set or empty');
        $this->_form->setData(array('OriginalId' => ''));
    }
}
