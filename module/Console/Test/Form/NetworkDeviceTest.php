<?php

/**
 * Tests for NetworkDevice form
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

namespace Console\Test\Form;

/**
 * Tests for NetworkDevice form
 */
class NetworkDeviceTest extends \Console\Test\AbstractFormTest
{
    protected $_deviceManager;

    protected function getForm()
    {
        $this->_deviceManager = $this->createMock('Model\Network\DeviceManager');
        $this->_deviceManager->method('getTypes')
                             ->will($this->returnValue(array()));
        $form = new \Console\Form\NetworkDevice();
        $form->setOption('DeviceManager', $this->_deviceManager);
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $deviceManager = $this->createMock('Model\Network\DeviceManager');
        $deviceManager->method('getTypes')
                      ->will($this->returnValue(array('cat1', 'cat2')));
        $form = new \Console\Form\NetworkDevice();
        $form->setOption('DeviceManager', $deviceManager);
        $form->init();

        $type = $form->get('Type');
        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $type);
        $this->assertEquals(array('cat1', 'cat2'), $type->getValueOptions());
        $this->assertInstanceOf('Laminas\Form\Element\Text', $form->get('Description'));
        $this->assertInstanceOf('\Library\Form\Element\Submit', $form->get('Submit'));
    }

    public function testInputFilterDescriptionTrim()
    {
        $this->_form->setValidationGroup(['Description']);
        $this->_form->setData(array('Description' => ' description '));
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('description', $this->_form->getData()['Description']);
    }

    public function testInputFilterDescriptionEmpty()
    {
        $this->_form->setValidationGroup(['Description']);
        $this->_form->setData(array('Description' => ''));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterDescriptionMax()
    {
        $this->_form->setValidationGroup(['Description']);
        $this->_form->setData(array('Description' => str_repeat('x', 255)));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterDescriptionTooLong()
    {
        $this->_form->setValidationGroup(['Description']);
        $this->_form->setData(array('Description' => str_repeat('x', 256)));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals(\Laminas\Validator\StringLength::TOO_LONG, key($messages['Description']));
    }
}
