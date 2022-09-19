<?php

/**
 * Tests for form localization
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

namespace Console\Test;

/**
 * Tests for form localization
 */
class FormLocalizationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test form object
     * @var \Console\Form\Form
     */
    protected $_form;

    public function setUp(): void
    {
        $this->_form = new \Console\Form\Form();
    }

    public function testLocalizeText()
    {
        $this->assertEquals('test', $this->_form->localize('test', 'text'));
    }

    public function testLocalizeIntegerValid()
    {
        $this->assertEquals('1.000.000', $this->_form->localize('1000000', 'integer'));
    }

    public function testLocalizeIntegerInvalid()
    {
        $this->assertEquals('1000000.', $this->_form->localize('1000000.', 'integer'));
    }

    public function testLocalizeIntegerEmpty()
    {
        $this->assertEquals('', $this->_form->localize('', 'integer'));
    }

    public function testLocalizeFloatValid()
    {
        $this->assertEquals('1.000.000', $this->_form->localize('1000000', 'float'));
        $this->assertEquals('1.000.000,1234', $this->_form->localize('1000000.1234', 'float'));
        $this->assertEquals('0,1234', $this->_form->localize('.1234', 'float'));
    }

    public function testLocalizeFloatInvalid()
    {
        $this->assertEquals('1000000.', $this->_form->localize('1000000.', 'float'));
    }

    public function testLocalizeFloatEmpty()
    {
        $this->assertEquals('', $this->_form->localize('', 'float'));
    }

    public function testLocalizeDateValid()
    {
        $this->assertEquals('01.05.2014', $this->_form->localize(new \DateTime('2014-05-01 00:00:01'), 'date'));
        $this->assertEquals('01.05.2014', $this->_form->localize(new \DateTime('2014-05-01 23:59:59'), 'date'));
        $this->assertEquals('01.05.2014', $this->_form->localize('2014-05-01', 'date'));
    }

    public function testLocalizeDateInvalid()
    {
        $this->assertEquals('invalid', $this->_form->localize('invalid', 'date'));
    }

    public function testLocalizeDateEmpty()
    {
        $this->assertEquals('', $this->_form->localize('', 'date'));
    }

    public function testLocalizeNull()
    {
        $this->assertNull($this->_form->localize(null, 'integer'));
        $this->assertNull($this->_form->localize(null, 'float'));
        $this->assertNull($this->_form->localize(null, 'date'));
    }

    public function testNormalizeText()
    {
        $this->assertEquals('test', $this->_form->normalize('test', 'text'));
    }

    public function testNormalizeIntegerValid()
    {
        $this->assertEquals(1234, $this->_form->normalize(' 1234 ', 'integer'));
        $this->assertEquals(1234, $this->_form->normalize(' 1.234 ', 'integer'));
        $this->assertEquals(1000, $this->_form->normalize(' 1.000 ', 'integer'));
    }

    public function testNormalizeIntegerInvalid()
    {
        $this->assertEquals('1,234', $this->_form->normalize(' 1,234 ', 'integer'));
        $this->assertEquals('1000.1', $this->_form->normalize(' 1000.1 ', 'integer'));
        $this->assertEquals('1000,1', $this->_form->normalize(' 1000,1 ', 'integer'));
        $this->assertEquals('1,000', $this->_form->normalize(' 1,000 ', 'integer'));
        $this->assertEquals('', $this->_form->normalize(' ', 'integer'));
    }

    public function testNormalizeFloatValid()
    {
        $this->assertEquals(1234, $this->_form->normalize(' 1.234 ', 'float'));
        $this->assertEquals(1.234, $this->_form->normalize(' 1,234 ', 'float'));
        $this->assertEquals(1234.5678, $this->_form->normalize(' 1.234,5678 ', 'float'));
        $this->assertEquals(1234.5678, $this->_form->normalize(' 1234,5678 ', 'float'));
    }

    public function testNormalizeFloatInvalid()
    {
        $this->assertEquals('1000.1', $this->_form->normalize(' 1000.1 ', 'float'));
        $this->assertEquals('1,234.5678', $this->_form->normalize(' 1,234.5678 ', 'float'));
        $this->assertEquals('', $this->_form->normalize(' ', 'float'));
    }

    public function testNormalizeDateValid()
    {
        $date = $this->_form->normalize(' 2.5.2014 ', 'date');
        $this->assertInstanceOf('DateTime', $date);
        $this->assertEquals('2014-05-02', $date->format('Y-m-d'));

        $date = $this->_form->normalize(' 02.05.2014 ', 'date');
        $this->assertInstanceOf('DateTime', $date);
        $this->assertEquals('2014-05-02', $date->format('Y-m-d'));
    }

    public function testNormalizeDateInvalid()
    {
        $this->assertEquals('31.2.2014', $this->_form->normalize(' 31.2.2014 ', 'date'));
        $this->assertEquals('2.5.2014 17:25:23', $this->_form->normalize(' 2.5.2014 17:25:23 ', 'date'));
        $this->assertEquals('2014-05-02', $this->_form->normalize(' 2014-05-02 ', 'date'));
        $this->assertEquals('05/01/2014', $this->_form->normalize(' 05/01/2014 ', 'date'));
        $this->assertEquals('05.01.14', $this->_form->normalize(' 05.01.14 ', 'date'));
        $this->assertEquals('', $this->_form->normalize(' ', 'date'));
    }

    public function testNormalizeNull()
    {
        $this->assertEquals('', $this->_form->normalize(null, 'integer'));
        $this->assertEquals('', $this->_form->normalize(null, 'float'));
        $this->assertEquals('', $this->_form->normalize(null, 'date'));
    }

    public function testValidateType()
    {
        $this->assertTrue($this->_form->validateType('', null, 'text'));
        $this->assertTrue($this->_form->validateType(0, null, 'text'));
        $this->assertTrue($this->_form->validateType(null, null, 'text'));

        $this->assertFalse($this->_form->validateType('', null, 'integer'));
        $this->assertTrue($this->_form->validateType(0, null, 'integer'));
        $this->assertFalse($this->_form->validateType(null, null, 'integer'));

        $this->assertFalse($this->_form->validateType('', null, 'float'));
        $this->assertTrue($this->_form->validateType(0.0, null, 'float'));
        $this->assertFalse($this->_form->validateType(null, null, 'float'));

        $this->assertFalse($this->_form->validateType('', null, 'date'));
        $this->assertTrue($this->_form->validateType(new \DateTime(), null, 'date'));
        $this->assertFalse($this->_form->validateType(null, null, 'date'));
    }
}
