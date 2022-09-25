<?php

/**
 * Tests for Agent form
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

use org\bovigo\vfs\vfsStream;

class AgentTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('contactInterval'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('inventoryInterval'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('agentWhitelistFile'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterValidNormalize()
    {
        $preferences = array(
            'contactInterval' => ' 1.234 ',
            'inventoryInterval' => ' 5.678 ',
            'agentWhitelistFile' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertEquals(1234, $preferences['contactInterval']);
        $this->assertEquals(5678, $preferences['inventoryInterval']);
    }

    public function testInputFilterValidMinValue()
    {
        $preferences = array(
            'contactInterval' => '1',
            'inventoryInterval' => '-1',
            'agentWhitelistFile' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInvalidValueTooSmall()
    {
        $preferences = array(
            'contactInterval' => '0',
            'inventoryInterval' => '-2',
            'agentWhitelistFile' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['contactInterval']);
        $this->assertArrayHasKey('notGreaterThan', $messages['contactInterval']);
        $this->assertCount(1, $messages['inventoryInterval']);
        $this->assertArrayHasKey('notGreaterThan', $messages['inventoryInterval']);
    }

    public function testInputFilterInvalidNonInteger()
    {
        $preferences = array(
            'contactInterval' => '1a',
            'inventoryInterval' => '2a',
            'agentWhitelistFile' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['contactInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['contactInterval']);
        $this->assertCount(1, $messages['inventoryInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['inventoryInterval']);
    }

    public function testInputFilterInvalidEmpty()
    {
        $preferences = array(
            'contactInterval' => '',
            'inventoryInterval' => ' ',
            'agentWhitelistFile' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['contactInterval']);
        $this->assertArrayHasKey('isEmpty', $messages['contactInterval']);
        $this->assertCount(1, $messages['inventoryInterval']);
        $this->assertArrayHasKey('isEmpty', $messages['inventoryInterval']);
    }

    public function testLocalizeIntegers()
    {
        $preferences = array(
            'contactInterval' => 1234,
            'inventoryInterval' => 5678,
            'agentWhitelistFile' => '0',
        );
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertSame(
            '1.234',
            $this->_form->get('Preferences')->get('contactInterval')->getValue()
        );
        $this->assertSame(
            '5.678',
            $this->_form->get('Preferences')->get('inventoryInterval')->getValue()
        );
    }

    public function testInputFilterInvalidFile()
    {
        $preferences = array(
            'contactInterval' => 1234,
            'inventoryInterval' => 5678,
            'agentWhitelistFile' => vfsStream::newFile('test', 0000)->at(vfsStream::setup('root'))->url(),
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['agentWhitelistFile']);
        $this->assertArrayHasKey('readable', $messages['agentWhitelistFile']);
    }

    public function testInputFilterValidFile()
    {
        $preferences = array(
            'contactInterval' => 1234,
            'inventoryInterval' => 5678,
            'agentWhitelistFile' => vfsStream::newFile('test', 0666)->at(vfsStream::setup('root'))->url(),
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }
}
