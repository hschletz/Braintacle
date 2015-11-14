<?php
/**
 * Tests for NotInArray validator
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
class NotInArrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Validation tests
     */
    public function testValidation()
    {
        $haystack = array('one', 'two');

        // Case sensitive match (default)
        $validator = new NotInArray(
            array(
                'haystack' => $haystack,
            )
        );
        $this->assertEquals($haystack, $validator->getHaystack());
        $this->assertEquals(NotInArray::CASE_SENSITIVE, $validator->getCaseSensitivity());
        $this->assertTrue($validator->isValid('three'));
        $this->assertTrue($validator->isValid('One'));
        $this->assertFalse($validator->isValid('one'));
        $this->assertEquals(
            array('inArray' => "'one' ist in der Liste ungültiger Werte"),
            $validator->getMessages()
        );

        // Case insensitive match
        $validator = new NotInArray(
            array(
                'haystack' => $haystack,
                'caseSensitivity' => NotInArray::CASE_INSENSITIVE,
            )
        );
        $this->assertEquals($haystack, $validator->getHaystack());
        $this->assertEquals(NotInArray::CASE_INSENSITIVE, $validator->getCaseSensitivity());
        $this->assertTrue($validator->isValid('three'));
        $this->assertFalse($validator->isValid('One'));
        $this->assertFalse($validator->isValid('one'));
    }

    /**
     * Test for Exception on invalid haystack
     */
    public function testInvalidHaystack()
    {
        $this->setExpectedException('RuntimeException');
        $validator = new NotInArray;
        $validator->isValid('test');
    }

    /**
     * Test for Exception on invalid caseSensitivity option
     */
    public function testInvalidCaseSensitivity()
    {
        $this->setExpectedException('InvalidArgumentException');
        $validator = new NotInArray(array('caseSensitivity' => true));
    }
}
