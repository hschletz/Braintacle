<?php

/**
 * Tests for Groups form
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

class GroupsTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('groupCacheExpirationInterval'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('groupCacheExpirationFuzz'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('setGroupPackageStatus'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterValidNormalize()
    {
        $preferences = array(
            'groupCacheExpirationInterval' => ' 1.234 ',
            'groupCacheExpirationFuzz' => ' 5.678 ',
            'setGroupPackageStatus' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertEquals(1234, $preferences['groupCacheExpirationInterval']);
        $this->assertEquals(5678, $preferences['groupCacheExpirationFuzz']);
    }

    public function testInputFilterValidMinValue()
    {
        $preferences = array(
            'groupCacheExpirationInterval' => '1',
            'groupCacheExpirationFuzz' => '1',
            'setGroupPackageStatus' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInvalidValueTooSmall()
    {
        $preferences = array(
            'groupCacheExpirationInterval' => '0',
            'groupCacheExpirationFuzz' => '0',
            'setGroupPackageStatus' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['groupCacheExpirationInterval']);
        $this->assertArrayHasKey('notGreaterThan', $messages['groupCacheExpirationInterval']);
        $this->assertCount(1, $messages['groupCacheExpirationFuzz']);
        $this->assertArrayHasKey('notGreaterThan', $messages['groupCacheExpirationFuzz']);
    }

    public function testInputFilterInvalidNonInteger()
    {
        $preferences = array(
            'groupCacheExpirationInterval' => '1a',
            'groupCacheExpirationFuzz' => '2a',
            'setGroupPackageStatus' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['groupCacheExpirationInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['groupCacheExpirationInterval']);
        $this->assertCount(1, $messages['groupCacheExpirationFuzz']);
        $this->assertArrayHasKey('callbackValue', $messages['groupCacheExpirationFuzz']);
    }

    public function testInputFilterInvalidEmpty()
    {
        $preferences = array(
            'groupCacheExpirationInterval' => '',
            'groupCacheExpirationFuzz' => ' ',
            'setGroupPackageStatus' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['groupCacheExpirationInterval']);
        $this->assertArrayHasKey('isEmpty', $messages['groupCacheExpirationInterval']);
        $this->assertCount(1, $messages['groupCacheExpirationFuzz']);
        $this->assertArrayHasKey('isEmpty', $messages['groupCacheExpirationFuzz']);
    }

    public function testInputFilterOnlyFirstIntegerInvalid()
    {
        $preferences = array(
            'groupCacheExpirationInterval' => '1a',
            'groupCacheExpirationFuzz' => '2',
            'setGroupPackageStatus' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['groupCacheExpirationInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['groupCacheExpirationInterval']);
    }

    public function testLocalizeIntegers()
    {
        $preferences = array(
            'groupCacheExpirationInterval' => 1234,
            'groupCacheExpirationFuzz' => 5678,
            'setGroupPackageStatus' => '0',
        );
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertSame(
            '1.234',
            $this->_form->get('Preferences')->get('groupCacheExpirationInterval')->getValue()
        );
        $this->assertSame(
            '5.678',
            $this->_form->get('Preferences')->get('groupCacheExpirationFuzz')->getValue()
        );
    }
}
