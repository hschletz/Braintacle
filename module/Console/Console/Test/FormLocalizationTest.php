<?php
/**
 * Tests for form localization
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

namespace Console\Test;

/**
 * Tests for form localization
 */
class FormLocalizationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test form object
     * @var \Console\Form\Form
     */
    protected $_form;

    public function setUp()
    {
        $this->_form = new \Console\Form\Form;
    }

    public function testLocalizeText()
    {
        $this->assertEquals('test', $this->_form->localize('text', 'test'));
    }

    public function testLocalizeIntegerValid()
    {
        $this->assertEquals('1.000.000', $this->_form->localize('integer', '1000000'));
    }

    public function testLocalizeIntegerInvalid()
    {
        $this->assertEquals('1000000.', $this->_form->localize('integer', '1000000.'));
    }

    public function testLocalizeIntegerEmpty()
    {
        $this->assertEquals('', $this->_form->localize('integer', ''));
    }

    public function testLocalizeFloatValid()
    {
        $this->assertEquals('1.000.000', $this->_form->localize('float', '1000000'));
        $this->assertEquals('1.000.000,1234', $this->_form->localize('float', '1000000.1234'));
        $this->assertEquals('0,1234', $this->_form->localize('float', '.1234'));
    }

    public function testLocalizeFloatInvalid()
    {
        $this->assertEquals('1000000.', $this->_form->localize('float', '1000000.'));
    }

    public function testLocalizeFloatEmpty()
    {
        $this->assertEquals('', $this->_form->localize('float', ''));
    }

    public function testLocalizeDateValid()
    {
        $this->assertEquals('01.05.2014', $this->_form->localize('date', new \Zend_Date('2014-05-01')));
        $this->assertEquals('01.05.2014', $this->_form->localize('date', '2014-05-01'));
    }

    public function testLocalizeDateInvalid()
    {
        $this->assertEquals('invalid', $this->_form->localize('date', 'invalid'));
    }

    public function testLocalizeDateEmpty()
    {
        $this->assertEquals('', $this->_form->localize('date', ''));
    }

    public function testNormalizeText()
    {
        $this->assertEquals('test', $this->_form->normalize('text', 'test'));
    }

    public function testNormalizeIntegerValid()
    {
        $this->assertEquals(1234, $this->_form->normalize('integer', ' 1234 '));
        $this->assertEquals(1234, $this->_form->normalize('integer', ' 1.234 '));
        $this->assertEquals(1000, $this->_form->normalize('integer', ' 1.000 '));
    }

    public function testNormalizeIntegerInvalid()
    {
        $this->assertEquals('1,234', $this->_form->normalize('integer', ' 1,234 '));
        $this->assertEquals('1000.1', $this->_form->normalize('integer', ' 1000.1 '));
        $this->assertEquals('1000,1', $this->_form->normalize('integer', ' 1000,1 '));
        $this->assertEquals('1,000', $this->_form->normalize('integer', ' 1,000 '));
        $this->assertEquals('', $this->_form->normalize('integer', ' '));
    }

    public function testNormalizeFloatValid()
    {
        $this->assertEquals(1234, $this->_form->normalize('float', ' 1.234 '));
        $this->assertEquals(1.234, $this->_form->normalize('float', ' 1,234 '));
        $this->assertEquals(1234.5678, $this->_form->normalize('float', ' 1.234,5678 '));
        $this->assertEquals(1234.5678, $this->_form->normalize('float', ' 1234,5678 '));
    }

    public function testNormalizeFloatInvalid()
    {
        $this->assertEquals('1000.1', $this->_form->normalize('float', ' 1000.1 '));
        $this->assertEquals('', $this->_form->normalize('float', ' '));
    }

    public function testNormalizeDateValid()
    {
        $this->assertEquals(
            '2014-05-02',
            $this->_form->normalize('date', ' 2.5.2014 ')->get('yyyy-MM-dd')
        );
        $this->assertEquals(
            '2014-05-02',
            $this->_form->normalize('date', ' 02.05.2014 ')->get('yyyy-MM-dd')
        );
    }

    public function testNormalizeDateInvalid()
    {
        $this->assertEquals('31.2.2014', $this->_form->normalize('date', ' 31.2.2014 '));
        $this->assertEquals('2.5.2014 17:25:23', $this->_form->normalize('date', ' 2.5.2014 17:25:23 '));
        $this->assertEquals('2014-05-02', $this->_form->normalize('date', ' 2014-05-02 '));
        $this->assertEquals('', $this->_form->normalize('date', ' '));
    }

    public function testValidateType()
    {
        $this->assertTrue($this->_form->validateType('text', ''));
        $this->assertTrue($this->_form->validateType('text', 0));
        $this->assertFalse($this->_form->validateType('integer', ''));
        $this->assertTrue($this->_form->validateType('integer', 0));
        $this->assertFalse($this->_form->validateType('float', ''));
        $this->assertTrue($this->_form->validateType('float', 0.0));
        $this->assertFalse($this->_form->validateType('date', ''));
        $this->assertTrue($this->_form->validateType('date', new \Zend_Date));
    }
}
