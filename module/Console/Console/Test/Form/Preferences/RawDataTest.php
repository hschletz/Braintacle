<?php
/**
 * Tests for DawData form
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

namespace Console\Test\Form\Preferences;
use \org\bovigo\vfs\vfsStream;

class RawDataTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $preferences->get('saveRawData'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $preferences->get('saveDir'));
        $saveFormat = $preferences->get('saveFormat');
        $this->assertInstanceOf('Zend\Form\Element\Select', $saveFormat);
        $this->assertEquals(
            array(
                'XML' => 'XML, unkomprimiert',
                'OCS' => 'XML, zlib-komprimiert',
            ),
            $saveFormat->getValueOptions()
        );
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $preferences->get('saveOverwrite'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterNoSaveNoDir()
    {
        $preferences = array(
            'saveRawData' => '0',
            'saveDir' => '',
            'saveFormat' => 'XML',
            'saveOverwrite' => '0',
        );
        $this->_form->setValidationGroup('Preferences');
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterSaveNoDir()
    {
        $preferences = array(
            'saveRawData' => '1',
            'saveDir' => '',
            'saveFormat' => 'XML',
            'saveOverwrite' => '0',
        );
        $this->_form->setValidationGroup('Preferences');
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['saveDir']);
        $this->assertArrayHasKey('isEmpty', $messages['saveDir']);
    }

    public function testInputFilterSaveInvalidDir()
    {
        $preferences = array(
            'saveRawData' => '1',
            'saveDir' => vfsStream::newDirectory('test', 0000)->at(vfsStream::setup('root'))->url(),
            'saveFormat' => 'XML',
            'saveOverwrite' => '0',
        );
        $this->_form->setValidationGroup('Preferences');
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['saveDir']);
        $this->assertArrayHasKey('writable', $messages['saveDir']);
    }

    public function testInputFilterSaveValidDir()
    {
        $preferences = array(
            'saveRawData' => '1',
            'saveDir' => vfsStream::newDirectory('test', 0777)->at(vfsStream::setup('root'))->url(),
            'saveFormat' => 'XML',
            'saveOverwrite' => '0',
        );
        $this->_form->setValidationGroup('Preferences');
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }
}
