<?php

/**
 * Tests for ProductKey validator
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

use Library\Validator\ProductKey;

/**
 * Tests for ProductKey validator
 */
class ProductKeyTest extends \PHPUnit\Framework\TestCase
{
    public function testValidKey()
    {
        $validator = new ProductKey();
        $this->assertTrue($validator->isValid('A1B2D-3E4F5-G6H7I-8J9K0-LMNOP'));
    }

    public function testInvalidLowercase()
    {
        $validator = new ProductKey();
        $this->assertFalse($validator->isValid('a1b2d-3e4f5-g6h7i-8j9k0-lmnop'));
    }

    public function testInvalidExtraBlock()
    {
        $validator = new ProductKey();
        $this->assertFalse($validator->isValid('A1B2D-3E4F5-G6H7I-8J9K0-LMNOP-QRSTU'));
    }

    public function testInvalidBadCharacters()
    {
        $validator = new ProductKey();
        $this->assertFalse($validator->isValid('A1B2D-3E4F5-G6H7I-8J9K0-LMNO%'));
    }

    public function testInvalidEmpty()
    {
        $validator = new ProductKey();
        $this->assertFalse($validator->isValid(''));
    }

    public function testMessage()
    {
        $validator = new ProductKey();
        $validator->isValid('invalid');
        $this->assertEquals(
            array(ProductKey::PRODUCT_KEY => "'invalid' is not a valid product key"),
            $validator->getMessages()
        );
    }
}
