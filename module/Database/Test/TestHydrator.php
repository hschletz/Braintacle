<?php
/**
 * Hydrator for AbstractTableTest
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Test;

/**
 * Hydrator for AbstractTableTest
 *
 * This is used for testing behavior with hydrators that don't inherit
 * AbstractHydrator.
 */
class TestHydrator implements \Zend\Hydrator\HydratorInterface
{
    public function hydrate(array $data, $object)
    {
        // unused, only required to implement HydratorInterface
    }

    public function extract($object)
    {
        // unused, only required to implement HydratorInterface
    }

    public function hydrateName()
    {
        // The hydrateName() method is provided by AbstractHydrator and must not
        // be called on hydrators that don't inherit from it.
        throw new \LogicException('hydrateName() must not be called');
    }
}
