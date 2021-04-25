<?php

/**
 * Hydrate inner iterator's results
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

namespace Library\Hydrator\Iterator;

/**
 * Hydrate inner iterator's results
 *
 * This is a wrapper for \Laminas\Hydrator\Iterator\HydratingIteratorIterator
 * with a fix for IteratorIterator's nonstandard behavior when current(), key()
 * and valid() are invoked directly. These methods are reimplemented by
 * correctly forwarding to the inner iterator.
 *
 * @see https://www.php.net/manual/en/class.iteratoriterator.php#120999
 */
class HydratingIteratorIterator extends \Laminas\Hydrator\Iterator\HydratingIteratorIterator
{
    public function current()
    {
        $currentValue = $this->getInnerIterator()->current();
        $object = clone $this->prototype;
        $this->hydrator->hydrate($currentValue, $object);

        return $object;
    }

    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    public function valid()
    {
        return $this->getInnerIterator()->valid();
    }
}
