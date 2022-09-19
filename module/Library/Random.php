<?php

/**
 * Service for generating random values
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

namespace Library;

/**
 * Service for generating random values
 *
 * This is a wrapper for \Laminas\Math\Rand. It has the advantage of being
 * available as the Library\Random service which can be replaced by a mock
 * object that generates predictable values for testing.
 *
 * The random generator functions are considered cryptographically secure.
 */
class Random
{
    /**
     * Generate random integer value
     *
     * @param integer $min lowest value to be returned
     * @param integer $max highest value to be returned
     * @return integer
     */
    public function getInteger($min, $max)
    {
        return \Laminas\Math\Rand::getInteger($min, $max);
    }
}
