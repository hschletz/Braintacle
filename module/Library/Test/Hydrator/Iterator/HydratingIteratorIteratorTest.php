<?php

/**
 * Tests for HydratingIteratorIterator
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Test\Hydrator\Iterator;

use ArrayIterator;
use ArrayObject;
use Laminas\Hydrator\ArraySerializableHydrator;
use Library\Hydrator\Iterator\HydratingIteratorIterator;

class HydratingIteratorIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testCurrent()
    {
        $data = ['a' => 'b'];

        $hydrator = new ArraySerializableHydrator();
        $prototype = new ArrayObject();
        $innerIterator = new ArrayIterator([$data]);
        $iterator = new HydratingIteratorIterator($hydrator, $innerIterator, $prototype);

        // Original implementation would return NULL
        $this->assertEquals($data, $iterator->current()->getArrayCopy());
    }

    public function testKey()
    {
        $data = ['a' => 'b'];

        $hydrator = new ArraySerializableHydrator();
        $prototype = new ArrayObject();
        $innerIterator = new ArrayIterator([$data]);
        $iterator = new HydratingIteratorIterator($hydrator, $innerIterator, $prototype);

        // Original implementation would return NULL
        $this->assertSame(0, $iterator->key());
    }

    public function testValid()
    {
        $data = ['a' => 'b'];

        $hydrator = new ArraySerializableHydrator();
        $prototype = new ArrayObject();
        $innerIterator = new ArrayIterator([$data]);
        $iterator = new HydratingIteratorIterator($hydrator, $innerIterator, $prototype);

        // Original implementation would return FALSE
        $this->assertTrue($iterator->valid());
    }
}
