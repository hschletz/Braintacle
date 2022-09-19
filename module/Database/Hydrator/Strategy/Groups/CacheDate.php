<?php

/**
 * Strategy for group cache dates
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

namespace Database\Hydrator\Strategy\Groups;

/**
 * Strategy for group cache dates
 *
 * Converts UNIX timestamps to \DateTime objects and vice versa, with an
 * optional offset to add/subtract. An extracted value of 0 is treated as NULL.
 */
class CacheDate implements \Laminas\Hydrator\Strategy\StrategyInterface
{
    /**
     * Seconds to add on hydration, subtract on extraction
     * @var integer
     */
    public $offset;

    /**
     * Constructor
     *
     * @param integer $offset Seconds to add on hydration, subtract on extraction (default: 0)
     */
    public function __construct($offset = 0)
    {
        $this->offset = $offset;
    }

    /** {@inheritdoc} */
    public function hydrate($value, ?array $data)
    {
        if ($value == 0 or $value == '') {
            $value = null;
        } else {
            $value = \DateTime::createFromFormat('U', $value + $this->offset);
        }
        return $value;
    }

    /** {@inheritdoc} */
    public function extract($value, ?object $object = null)
    {
        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp() - $this->offset;
        } else {
            $value = 0;
        }
        return $value;
    }
}
