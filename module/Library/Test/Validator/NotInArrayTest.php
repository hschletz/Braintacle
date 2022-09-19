<?php

/**
 * Tests for NotInArray validator
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

namespace Library\Test\Validator;

use Library\Validator\NotInArray;

/**
 * Tests for NotInArray validator
 */
class NotInArrayTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHaystackSetViaConstructor()
    {
        $haystack = array('one', 'two');
        $validator = new NotInArray(array('haystack' => $haystack));
        $this->assertEquals($haystack, $validator->getHaystack());
    }

    public function testGetHaystackSetViaSetHaystack()
    {
        $haystack = array('one', 'two');
        $validator = new NotInArray();
        $validator->setHaystack($haystack);
        $this->assertEquals($haystack, $validator->getHaystack());
    }

    public function testInvalidHaystack()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Haystack is not an array');
        $validator = new NotInArray();
        $validator->isValid('test');
    }

    public function testCaseSensitivityFlagDefaultCaseSensitive()
    {
        $validator = new NotInArray();
        $this->assertEquals(NotInArray::CASE_SENSITIVE, $validator->getCaseSensitivity());
    }

    public function testCaseSensitivityFlagCaseInsensitive()
    {
        $validator = new NotInArray(array('caseSensitivity' => NotInArray::CASE_INSENSITIVE));
        $this->assertEquals(NotInArray::CASE_INSENSITIVE, $validator->getCaseSensitivity());
    }

    public function testInvalidCaseSensitivity()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid value for caseSensitivity option: 1');
        $validator = new NotInArray(array('caseSensitivity' => true));
    }

    public function validationProvider()
    {
        return array(
            array('three', NotInArray::CASE_SENSITIVE, true),
            array('One', NotInArray::CASE_SENSITIVE, true),
            array('one', NotInArray::CASE_SENSITIVE, false),
            array('three', NotInArray::CASE_INSENSITIVE, true),
            array('One', NotInArray::CASE_INSENSITIVE, false),
            array('one', NotInArray::CASE_INSENSITIVE, false),
        );
    }

    /**
     * @dataProvider validationProvider
     */
    public function testValidation($value, $caseSensitivity, $expectedResult)
    {
        $haystack = array('one', 'two');

        $validator = new NotInArray(
            array(
                'haystack' => $haystack,
                'caseSensitivity' => $caseSensitivity,
            )
        );
        $this->assertSame($expectedResult, $validator->isValid($value));
    }

    public function testMessage()
    {
        $validator = new NotInArray(array('haystack' => array('one')));
        $validator->isValid('one');
        $this->assertEquals(
            array(NotInArray::IN_ARRAY => "'one' is in the list of invalid values"),
            $validator->getMessages()
        );
    }
}
