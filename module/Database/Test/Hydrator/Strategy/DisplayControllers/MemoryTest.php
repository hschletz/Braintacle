<?php

/**
 * Tests for Memory strategy
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Test\Hydrator\Strategy\DisplayControllers;

use Database\Test\Hydrator\Strategy\AbstractStrategyTestCase;

class MemoryTest extends AbstractStrategyTestCase
{
    public static function hydrateProvider()
    {
        return array(
            array('0', null),
            array(0, null),
            array('0a', '0a'),
        );
    }

    public static function extractProvider()
    {
        return array(
            array('128', '128'),
            array(null, null),
        );
    }
}
