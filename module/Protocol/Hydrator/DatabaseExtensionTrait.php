<?php

/**
 * Extension of database hydrator methods
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

namespace Protocol\Hydrator;

/**
 * Extension of database hydrator methods
 *
 * This trait can be used by hydrators that extend their database counterpart
 * instead of accessing it via DatabaseProxy. It implements the name case
 * conversion.
 */
trait DatabaseExtensionTrait
{
    /**
     * Hydrate object
     *
     * Calls parent method with names converted to lowercase first.
     *
     * @param array $data
     * @param object $object
     * @return object
     */
    public function hydrate(array $data, $object)
    {
        return parent::hydrate(array_change_key_case($data, CASE_LOWER), $object);
    }

    /**
     * Extract data
     *
     * Calls parent method and converts names to uppercase.
     *
     * @param object $object
     * @return array
     */
    public function extract(object $object): array
    {
        return array_change_key_case(parent::extract($object), CASE_UPPER);
    }

    /**
     * Hydrate name
     *
     * Calls parent method with name converted to lowercase first.
     *
     * @param string $name
     * @return string
     */
    public function hydrateName($name)
    {
        return parent::hydrateName(strtolower($name));
    }

    /**
     * Extract name
     *
     * Calls parent method and converts name to uppercase.
     *
     * @param string $name
     * @return string
     */
    public function extractName($name)
    {
        return strtoupper(parent::extractName($name));
    }
}
