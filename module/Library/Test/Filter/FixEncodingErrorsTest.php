<?php

/**
 * Tests for FixEncodingErrors filter
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

namespace Library\Test\Filter;

/**
 * Tests for FixEncodingErrors filter
 */
class FixEncodingErrorsTest extends \PHPUnit\Framework\TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf('Laminas\Filter\AbstractFilter', new \Library\Filter\FixEncodingErrors());
    }

    public function testFilter()
    {
        // 2 common example characters
        $enDashBad  = "\xC2\x96";
        $enDashGood = "\xE2\x80\x93";
        $tmBad      = "\xC2\x99";
        $tmGood     = "\xE2\x84\xA2";

        // Test 1 character twice to check repeated replacements
        $input = $enDashBad . $tmBad . $enDashBad;
        $expected = $enDashGood . $tmGood . $enDashGood;
        $this->assertEquals(
            $expected,
            \Laminas\Filter\StaticFilter::execute($input, 'Library\FixEncodingErrors')
        );
    }
}
