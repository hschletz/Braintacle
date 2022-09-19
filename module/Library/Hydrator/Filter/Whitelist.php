<?php

/**
 * Filter to keep only whitelisted properties
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

namespace Library\Hydrator\Filter;

/**
 * Filter to keep only whitelisted properties
 */
class Whitelist implements \Laminas\Hydrator\Filter\FilterInterface
{
    /**
     * Whitelisted properties
     * @var string[]
     */
    protected $_whitelist;

    /**
     * Constructor
     *
     * @param string[] $whitelist Whitelisted properties
     */
    public function __construct(array $whitelist)
    {
        $this->_whitelist = $whitelist;
    }

    /** {@inheritdoc} */
    public function filter(string $property, ?object $instance = null): bool
    {
        return in_array($property, $this->_whitelist);
    }
}
