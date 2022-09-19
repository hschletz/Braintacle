<?php

/**
 * Tests for Subnet form
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
 * Tests for Subnet form
 */
class SubnetTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('Name'));
        $this->assertInstanceOf('\Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterNameEmpty()
    {
        $data = array(
            'Name' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterNameMax()
    {
        $data = array(
            'Name' => str_repeat('x', 255),
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterNameTooLong()
    {
        $data = array(
            'Name' => str_repeat('x', 256),
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals(\Laminas\Validator\StringLength::TOO_LONG, key($messages['Name']));
    }
}
