<?php

/**
 * Convert values to integer on hydration, preserving NULL
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

namespace Library\Hydrator\Strategy;

/**
 * Convert values to integer on hydration, preserving NULL
 */
class Integer implements \Laminas\Hydrator\Strategy\StrategyInterface
{
    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException if $value has invalid datatype or content
     */
    public function hydrate($value, ?array $data)
    {
        if (is_integer($value) or $value === null) {
            return $value;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Expected integer or string input, got ' . gettype($value));
        }
        if (!ctype_digit($value)) {
            throw new \InvalidArgumentException('Non-integer input value: ' . $value);
        }
        return (int) $value;
    }

    /** {@inheritdoc} */
    public function extract($value, ?object $object = null)
    {
        return $value;
    }
}
