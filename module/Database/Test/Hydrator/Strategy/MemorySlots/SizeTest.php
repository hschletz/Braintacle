<?php

/**
 * Tests for Size strategy
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

namespace Database\Test\Hydrator\Strategy\MemorySlots;

class SizeTest extends \Database\Test\Hydrator\Strategy\AbstractStrategyTest
{
    public function hydrateProvider()
    {
        return array(
            array('128', 128),
            array(128, 128),
            array('128a', null),
            array('No', null),
            array('0', null),
            array(0, null),
            array(null, null),
        );
    }

    public function extractProvider()
    {
        return array(
            array('128', '128'),
            array('0', '0'),
            array(null, null),
        );
    }
}
