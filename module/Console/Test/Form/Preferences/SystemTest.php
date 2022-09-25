<?php

/**
 * Tests for System form
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

namespace Console\Test\Form\Preferences;

class SystemTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('communicationServerUri'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('lockValidity'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('sessionValidity'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('sessionCleanupInterval'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('sessionRequired'));
        $logLevel = $preferences->get('logLevel');
        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $logLevel);
        $this->assertEquals(array(0, 1, 2), $logLevel->getValueOptions());
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('validateXml'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('autoMergeDuplicates'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterValidNormalize()
    {
        $preferences = array(
            'communicationServerUri' => 'http://example.net',
            'lockValidity' => ' 1.234 ',
            'sessionValidity' => ' 2.345 ',
            'sessionCleanupInterval' => ' 3.456 ',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertEquals(1234, $preferences['lockValidity']);
        $this->assertEquals(2345, $preferences['sessionValidity']);
        $this->assertEquals(3456, $preferences['sessionCleanupInterval']);
    }

    public function testInputFilterValidMinValue()
    {
        $preferences = array(
            'communicationServerUri' => 'http://example.net',
            'lockValidity' => ' 1 ',
            'sessionValidity' => ' 1 ',
            'sessionCleanupInterval' => ' 1 ',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInvalidValueTooSmall()
    {
        $preferences = array(
            'communicationServerUri' => 'http://example.net',
            'lockValidity' => ' 0 ',
            'sessionValidity' => ' 0 ',
            'sessionCleanupInterval' => ' 0 ',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(3, $messages);
        $this->assertCount(1, $messages['lockValidity']);
        $this->assertArrayHasKey('notGreaterThan', $messages['lockValidity']);
        $this->assertCount(1, $messages['sessionValidity']);
        $this->assertArrayHasKey('notGreaterThan', $messages['sessionValidity']);
        $this->assertCount(1, $messages['sessionCleanupInterval']);
        $this->assertArrayHasKey('notGreaterThan', $messages['sessionCleanupInterval']);
    }

    public function testInputFilterInvalidNonInteger()
    {
        $preferences = array(
            'communicationServerUri' => 'http://example.net',
            'lockValidity' => ' 1.234a ',
            'sessionValidity' => ' 2.345a ',
            'sessionCleanupInterval' => ' 3.456a ',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(3, $messages);
        $this->assertCount(1, $messages['lockValidity']);
        $this->assertArrayHasKey('callbackValue', $messages['lockValidity']);
        $this->assertCount(1, $messages['sessionValidity']);
        $this->assertArrayHasKey('callbackValue', $messages['sessionValidity']);
        $this->assertCount(1, $messages['sessionCleanupInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['sessionCleanupInterval']);
    }

    public function testInputFilterInvalidEmpty()
    {
        $preferences = array(
            'communicationServerUri' => '',
            'lockValidity' => ' ',
            'sessionValidity' => '',
            'sessionCleanupInterval' => '',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(4, $messages);
        $this->assertCount(1, $messages['communicationServerUri']);
        $this->assertArrayHasKey('isEmpty', $messages['communicationServerUri']);
        $this->assertCount(1, $messages['lockValidity']);
        $this->assertArrayHasKey('isEmpty', $messages['lockValidity']);
        $this->assertCount(1, $messages['sessionValidity']);
        $this->assertArrayHasKey('isEmpty', $messages['sessionValidity']);
        $this->assertCount(1, $messages['sessionCleanupInterval']);
        $this->assertArrayHasKey('isEmpty', $messages['sessionCleanupInterval']);
    }

    public function testLocalizeIntegers()
    {
        $preferences = array(
            'communicationServerUri' => 'http://example.net',
            'lockValidity' => 1234,
            'sessionValidity' => 2345,
            'sessionCleanupInterval' => 3456,
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertSame(
            '1.234',
            $this->_form->get('Preferences')->get('lockValidity')->getValue()
        );
        $this->assertSame(
            '2.345',
            $this->_form->get('Preferences')->get('sessionValidity')->getValue()
        );
        $this->assertSame(
            '3.456',
            $this->_form->get('Preferences')->get('sessionCleanupInterval')->getValue()
        );
    }

    public function testInputFilterInvalidUriNoScheme()
    {
        $preferences = array(
            'communicationServerUri' => 'example.net',
            'lockValidity' => ' 1.234 ',
            'sessionValidity' => ' 2.345 ',
            'sessionCleanupInterval' => ' 3.456 ',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['communicationServerUri']);
        $this->assertArrayHasKey('notUri', $messages['communicationServerUri']);
    }

    public function testInputFilterInvalidUriIncorrectScheme()
    {
        $preferences = array(
            'communicationServerUri' => 'ftp://example.net',
            'lockValidity' => ' 1.234 ',
            'sessionValidity' => ' 2.345 ',
            'sessionCleanupInterval' => ' 3.456 ',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['communicationServerUri']);
        $this->assertArrayHasKey('notUri', $messages['communicationServerUri']);
    }

    public function testInputFilterInvalidUriRelative()
    {
        $preferences = array(
            'communicationServerUri' => 'path',
            'lockValidity' => ' 1.234 ',
            'sessionValidity' => ' 2.345 ',
            'sessionCleanupInterval' => ' 3.456 ',
            'sessionRequired' => '0',
            'logLevel' => '0',
            'validateXml' => '0',
            'autoMergeDuplicates' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['communicationServerUri']);
        $this->assertArrayHasKey('notUri', $messages['communicationServerUri']);
    }
}
