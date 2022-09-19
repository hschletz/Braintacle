<?php

/**
 * Tests for \Library\Form\Element\SelectSimple
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

namespace Library\Test\Form\Element;

/**
 * Tests for \Library\Form\Element\SelectSimple
 */
class SelectSimpleTest extends \PHPUnit\Framework\TestCase
{
    public function testInArrayValidator()
    {
        $element = new \Library\Form\Element\SelectSimple();
        $element->setValueOptions(array('option1', 'option2'));
        $factory = new \Laminas\InputFilter\Factory();
        $input = $factory->createInput($element->getInputSpecification());
        $input->setValue('option1');
        $this->assertTrue($input->isValid());
        $input->setValue('option2');
        $this->assertTrue($input->isValid());
        $input->setValue('option3');
        $this->assertFalse($input->isValid());
    }

    public function testUncallableCode()
    {
        $this->expectException(
            'LogicException',
            'Library\Form\Element\SelectSimple::getOptionValue() should never be called'
        );
        $method = new \ReflectionMethod('Library\Form\Element\SelectSimple', 'getOptionValue');
        $method->setAccessible(true);
        $method->invoke(new \Library\Form\Element\SelectSimple(), '', array());
    }
}
