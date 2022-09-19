<?php

/**
 * Tests for EmptyArray filter
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

class EmptyArrayTest extends \PHPUnit\Framework\TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf('Laminas\Filter\AbstractFilter', new \Library\Filter\EmptyArray());
    }

    public function testFilter()
    {
        $filter = new \Library\Filter\EmptyArray();
        $this->assertEquals([], $filter->filter(null));
        $this->assertEquals([], $filter->filter(''));
        $this->assertEquals([], $filter->filter([]));
        $this->assertEquals([], $filter->filter(0));
        $this->assertEquals([], $filter->filter(false));
        $this->assertEquals('foo', $filter->filter('foo'));
        $this->assertEquals(1, $filter->filter(1));
    }
}
