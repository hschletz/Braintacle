<?php

/**
 * Tests for Download form
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

class DownloadTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('packageDeployment'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('packagePath'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('packageBaseUriHttp'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('packageBaseUriHttps'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('downloadPeriodDelay'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('downloadCycleDelay'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('downloadFragmentDelay'));
        $downloadMaxPriority = $preferences->get('downloadMaxPriority');
        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $downloadMaxPriority);
        $this->assertEquals(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10), $downloadMaxPriority->getValueOptions());
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('downloadTimeout'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterValidNormalize()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' http://example.net/path/ ',
            'packageBaseUriHttps' => ' https://example.net/path/ ',
            'downloadPeriodDelay' => ' 1.234 ',
            'downloadCycleDelay' => ' 2.345 ',
            'downloadFragmentDelay' => ' 3.456 ',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => ' 4.567 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertEquals('example.net/path', $preferences['packageBaseUriHttp']);
        $this->assertEquals('example.net/path', $preferences['packageBaseUriHttps']);
        $this->assertEquals(1234, $preferences['downloadPeriodDelay']);
        $this->assertEquals(2345, $preferences['downloadCycleDelay']);
        $this->assertEquals(3456, $preferences['downloadFragmentDelay']);
        $this->assertEquals(4567, $preferences['downloadTimeout']);
    }

    public function testInputFilterValidMinValue()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' http://example.net/path/ ',
            'packageBaseUriHttps' => ' https://example.net/path/ ',
            'downloadPeriodDelay' => ' 1 ',
            'downloadCycleDelay' => ' 1 ',
            'downloadFragmentDelay' => ' 1 ',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => ' 1 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInvalidValueTooSmall()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' http://example.net/path/ ',
            'packageBaseUriHttps' => ' https://example.net/path/ ',
            'downloadPeriodDelay' => ' 0 ',
            'downloadCycleDelay' => ' 0 ',
            'downloadFragmentDelay' => ' 0 ',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => ' 0 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(4, $messages);
        $this->assertCount(1, $messages['downloadPeriodDelay']);
        $this->assertArrayHasKey('notGreaterThan', $messages['downloadPeriodDelay']);
        $this->assertCount(1, $messages['downloadCycleDelay']);
        $this->assertArrayHasKey('notGreaterThan', $messages['downloadCycleDelay']);
        $this->assertCount(1, $messages['downloadFragmentDelay']);
        $this->assertArrayHasKey('notGreaterThan', $messages['downloadFragmentDelay']);
        $this->assertCount(1, $messages['downloadTimeout']);
        $this->assertArrayHasKey('notGreaterThan', $messages['downloadTimeout']);
    }

    public function testInputFilterInvalidNonInteger()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' http://example.net/path/ ',
            'packageBaseUriHttps' => ' https://example.net/path/ ',
            'downloadPeriodDelay' => ' 1.234a ',
            'downloadCycleDelay' => ' 2.345a ',
            'downloadFragmentDelay' => ' 3.456a ',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => ' 4.567a ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(4, $messages);
        $this->assertCount(1, $messages['downloadPeriodDelay']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadPeriodDelay']);
        $this->assertCount(1, $messages['downloadCycleDelay']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadCycleDelay']);
        $this->assertCount(1, $messages['downloadFragmentDelay']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadFragmentDelay']);
        $this->assertCount(1, $messages['downloadTimeout']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadTimeout']);
    }

    public function testInputFilterInvalidEmpty()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => '',
            'packageBaseUriHttp' => ' ',
            'packageBaseUriHttps' => '',
            'downloadPeriodDelay' => ' ',
            'downloadCycleDelay' => '',
            'downloadFragmentDelay' => '',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => '',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(7, $messages);
        $this->assertCount(1, $messages['packagePath']);
        $this->assertArrayHasKey('isEmpty', $messages['packagePath']);
        $this->assertCount(1, $messages['packageBaseUriHttp']);
        $this->assertArrayHasKey('isEmpty', $messages['packageBaseUriHttp']);
        $this->assertCount(1, $messages['packageBaseUriHttps']);
        $this->assertArrayHasKey('isEmpty', $messages['packageBaseUriHttps']);
        $this->assertCount(1, $messages['downloadPeriodDelay']);
        $this->assertArrayHasKey('isEmpty', $messages['downloadPeriodDelay']);
        $this->assertCount(1, $messages['downloadCycleDelay']);
        $this->assertArrayHasKey('isEmpty', $messages['downloadCycleDelay']);
        $this->assertCount(1, $messages['downloadFragmentDelay']);
        $this->assertArrayHasKey('isEmpty', $messages['downloadFragmentDelay']);
        $this->assertCount(1, $messages['downloadTimeout']);
        $this->assertArrayHasKey('isEmpty', $messages['downloadTimeout']);
    }

    public function testLocalizeIntegers()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' http://example.net/path/ ',
            'packageBaseUriHttps' => ' https://example.net/path/ ',
            'downloadPeriodDelay' => '1234',
            'downloadCycleDelay' => '2345',
            'downloadFragmentDelay' => '3456',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => '4567',
        );
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertSame(
            '1.234',
            $this->_form->get('Preferences')->get('downloadPeriodDelay')->getValue()
        );
        $this->assertSame(
            '2.345',
            $this->_form->get('Preferences')->get('downloadCycleDelay')->getValue()
        );
        $this->assertSame(
            '3.456',
            $this->_form->get('Preferences')->get('downloadFragmentDelay')->getValue()
        );
        $this->assertSame(
            '4.567',
            $this->_form->get('Preferences')->get('downloadTimeout')->getValue()
        );
    }

    public function testInputFilterInvalidPath()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0000)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' http://example.net/path/ ',
            'packageBaseUriHttps' => ' https://example.net/path/ ',
            'downloadPeriodDelay' => ' 1.234 ',
            'downloadCycleDelay' => ' 2.345 ',
            'downloadFragmentDelay' => ' 3.456 ',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => ' 4.567 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['packagePath']);
        $this->assertArrayHasKey('writable', $messages['packagePath']);
    }

    public function testInputFilterValidUriNoScheme()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' example.net/path ',
            'packageBaseUriHttps' => ' example.net/path ',
            'downloadPeriodDelay' => ' 1.234 ',
            'downloadCycleDelay' => ' 2.345 ',
            'downloadFragmentDelay' => ' 3.456 ',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => ' 4.567 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertEquals('example.net/path', $preferences['packageBaseUriHttp']);
        $this->assertEquals('example.net/path', $preferences['packageBaseUriHttps']);
    }

    public function testInputFilterInvalidUri()
    {
        $preferences = array(
            'packageDeployment' => '1',
            'packagePath' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'packageBaseUriHttp' => ' example.net path ',
            'packageBaseUriHttps' => ' example.net path ',
            'downloadPeriodDelay' => ' 1.234 ',
            'downloadCycleDelay' => ' 2.345 ',
            'downloadFragmentDelay' => ' 3.456 ',
            'downloadMaxPriority' => '10',
            'downloadTimeout' => ' 4.567 ',
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['packageBaseUriHttp']);
        $this->assertArrayHasKey('callbackValue', $messages['packageBaseUriHttp']);
        $this->assertCount(1, $messages['packageBaseUriHttps']);
        $this->assertArrayHasKey('callbackValue', $messages['packageBaseUriHttps']);
    }
}
