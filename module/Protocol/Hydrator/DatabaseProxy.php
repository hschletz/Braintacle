<?php

/**
 * Hydrator that proxies to a database hydrator
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
 * Hydrator that proxies to a database hydrator
 *
 * Most elements in an InventoryRequest document map directly to a database
 * column with the same name. The only difference is the spelling of
 * element/column names (element: uppercase; column: lowercase).
 *
 * This hydrator proxies to a matching database hydrator, handling the case
 * conversion. It covers almost every use case except date/timestamp columns
 * where the XML document uses various non-ISO formats.
 */
class DatabaseProxy implements \Laminas\Hydrator\HydratorInterface
{
    /**
     * Proxied hydrator
     * @var \Laminas\Hydrator\HydratorInterface
     */
    protected $_hydrator;

    /**
     * Constructor
     *
     * @param \Laminas\Hydrator\HydratorInterface $hydrator
     */
    public function __construct(\Laminas\Hydrator\HydratorInterface $hydrator)
    {
        $this->_hydrator = $hydrator;
    }

    /**
     * Return attached hydrator
     */
    public function getHydrator(): \Laminas\Hydrator\HydratorInterface
    {
        return $this->_hydrator;
    }

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        return $this->_hydrator->hydrate(array_change_key_case($data, CASE_LOWER), $object);
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        return array_change_key_case($this->_hydrator->extract($object), CASE_UPPER);
    }
}
