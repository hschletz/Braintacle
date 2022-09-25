<?php

/**
 * Tests for Filters form
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

class FiltersTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('trustedNetworksOnly'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('inventoryFilter'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('limitInventoryInterval'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterValidNoInterval()
    {
        $preferences = array(
            'trustedNetworksOnly' => '0',
            'inventoryFilter' => '0',
            'limitInventoryInterval' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterValidInterval()
    {
        $preferences = array(
            'trustedNetworksOnly' => '0',
            'inventoryFilter' => '0',
            'limitInventoryInterval' => ' 1.234 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234, $this->_form->getData()['Preferences']['limitInventoryInterval']);
    }

    public function testInputFilterInvalidInterval()
    {
        $preferences = array(
            'trustedNetworksOnly' => '0',
            'inventoryFilter' => '0',
            'limitInventoryInterval' => ' 42a ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['limitInventoryInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['limitInventoryInterval']);
    }

    public function testInputFilterInvalidIntervalTooSmall()
    {
        $preferences = array(
            'trustedNetworksOnly' => '0',
            'inventoryFilter' => '0',
            'limitInventoryInterval' => '0',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['limitInventoryInterval']);
        $this->assertArrayHasKey('notGreaterThan', $messages['limitInventoryInterval']);
    }

    public function testLocalizeIntegers()
    {
        $preferences = array(
            'trustedNetworksOnly' => '0',
            'inventoryFilter' => '0',
            'limitInventoryInterval' => 1234,
        );
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertSame(
            '1.234',
            $this->_form->get('Preferences')->get('limitInventoryInterval')->getValue()
        );
    }
}
