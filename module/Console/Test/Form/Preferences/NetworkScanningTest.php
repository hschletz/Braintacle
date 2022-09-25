<?php

/**
 * Tests for NetworkScanning form
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

class NetworkScanningTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('scannersPerSubnet'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('scanSnmp'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('scannerMinDays'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('scannerMaxDays'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('scanArpDelay'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterValidNormalize()
    {
        $preferences = array(
            'scannersPerSubnet' => ' 1.234 ',
            'scanSnmp' => '0',
            'scannerMinDays' => ' 2.345 ',
            'scannerMaxDays' => ' 3.456 ',
            'scanArpDelay' => ' 4.567 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertEquals(1234, $preferences['scannersPerSubnet']);
        $this->assertEquals(2345, $preferences['scannerMinDays']);
        $this->assertEquals(3456, $preferences['scannerMaxDays']);
        $this->assertEquals(4567, $preferences['scanArpDelay']);
    }

    public function testInputFilterValidMinValue()
    {
        $preferences = array(
            'scannersPerSubnet' => '0',
            'scanSnmp' => '0',
            'scannerMinDays' => '1',
            'scannerMaxDays' => '1',
            'scanArpDelay' => '10',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInvalidValueTooSmall()
    {
        $preferences = array(
            'scannersPerSubnet' => '-1',
            'scanSnmp' => '0',
            'scannerMinDays' => '0',
            'scannerMaxDays' => '0',
            'scanArpDelay' => '9',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(4, $messages);
        $this->assertCount(1, $messages['scannersPerSubnet']);
        $this->assertArrayHasKey('notGreaterThan', $messages['scannersPerSubnet']);
        $this->assertCount(1, $messages['scannerMinDays']);
        $this->assertArrayHasKey('notGreaterThan', $messages['scannerMinDays']);
        $this->assertCount(1, $messages['scannerMaxDays']);
        $this->assertArrayHasKey('notGreaterThan', $messages['scannerMaxDays']);
        $this->assertCount(1, $messages['scanArpDelay']);
        $this->assertArrayHasKey('notGreaterThan', $messages['scanArpDelay']);
    }

    public function testInputFilterInvalidNonInteger()
    {
        $preferences = array(
            'scannersPerSubnet' => '1a',
            'scanSnmp' => '0',
            'scannerMinDays' => '2a',
            'scannerMaxDays' => '3a',
            'scanArpDelay' => '4a',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(4, $messages);
        $this->assertCount(1, $messages['scannersPerSubnet']);
        $this->assertArrayHasKey('callbackValue', $messages['scannersPerSubnet']);
        $this->assertCount(1, $messages['scannerMinDays']);
        $this->assertArrayHasKey('callbackValue', $messages['scannerMinDays']);
        $this->assertCount(1, $messages['scannerMaxDays']);
        $this->assertArrayHasKey('callbackValue', $messages['scannerMaxDays']);
        $this->assertCount(1, $messages['scanArpDelay']);
        $this->assertArrayHasKey('callbackValue', $messages['scanArpDelay']);
    }

    public function testInputFilterInvalidEmpty()
    {
        $preferences = array(
            'scannersPerSubnet' => ' ',
            'scanSnmp' => '0',
            'scannerMinDays' => '',
            'scannerMaxDays' => '',
            'scanArpDelay' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(4, $messages);
        $this->assertCount(1, $messages['scannersPerSubnet']);
        $this->assertArrayHasKey('isEmpty', $messages['scannersPerSubnet']);
        $this->assertCount(1, $messages['scannerMinDays']);
        $this->assertArrayHasKey('isEmpty', $messages['scannerMinDays']);
        $this->assertCount(1, $messages['scannerMaxDays']);
        $this->assertArrayHasKey('isEmpty', $messages['scannerMaxDays']);
        $this->assertCount(1, $messages['scanArpDelay']);
        $this->assertArrayHasKey('isEmpty', $messages['scanArpDelay']);
    }

    public function testLocalizeIntegers()
    {
        $preferences = array(
            'scannersPerSubnet' => 1234,
            'scanSnmp' => '0',
            'scannerMinDays' => 2345,
            'scannerMaxDays' => 3456,
            'scanArpDelay' => 4567,
        );
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertSame(
            '1.234',
            $this->_form->get('Preferences')->get('scannersPerSubnet')->getValue()
        );
        $this->assertSame(
            '2.345',
            $this->_form->get('Preferences')->get('scannerMinDays')->getValue()
        );
        $this->assertSame(
            '3.456',
            $this->_form->get('Preferences')->get('scannerMaxDays')->getValue()
        );
        $this->assertSame(
            '4.567',
            $this->_form->get('Preferences')->get('scanArpDelay')->getValue()
        );
    }
}
