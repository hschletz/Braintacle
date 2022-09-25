<?php

/**
 * Tests for DawData form
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
use Laminas\Dom\Document\Query;

class RawDataTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('saveRawData'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('saveDir'));
        $this->assertInstanceOf('Laminas\Form\Element\Select', $preferences->get('saveFormat'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('saveOverwrite'));
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
        $this->_form->setValidationGroup(['Preferences']);
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
        $this->_form->setValidationGroup(['Preferences']);
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
        $this->_form->setValidationGroup(['Preferences']);
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
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
    }

    public function testSelectOptionsTranslated()
    {
        $view = $this->createView();
        $preferences = $this->_form->get('Preferences');
        $html = $this->_form->renderFieldset($view, $preferences);
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(2, Query::execute('//select[@name="saveFormat"]/option', $document));
        $this->assertCount(
            1,
            Query::execute(
                '//select[@name="saveFormat"]/option[@value="XML"][text()="XML, unkomprimiert"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '//select[@name="saveFormat"]/option[@value="OCS"][text()="XML, zlib-komprimiert"]',
                $document
            )
        );
    }
}
