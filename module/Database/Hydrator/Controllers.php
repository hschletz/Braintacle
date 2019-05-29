<?php
/**
 * Hydrator for controllers
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

namespace Database\Hydrator;

/**
 * Hydrator for controllers
 *
 * Sanitizes incompatible structures produced by different agents.
 */
class Controllers implements \Zend\Hydrator\HydratorInterface
{
    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        $object->exchangeArray(array());
        if ($data['is_windows']) {
            $object['Type'] = $data['type'];
            $object['Name'] = $data['name'];
            $object['Version'] = $data['version'];
            $object['Manufacturer'] = $data['manufacturer'];
            $object['Comment'] = $data['description'];
        } else {
            $object['Type'] = $data['name'];
            $object['Name'] = $data['manufacturer'];
            $object['Version'] = $data['type'];
        }
        return $object;
    }

    /** {@inheritdoc} */
    public function extract($object)
    {
        $data = array();
        if (array_key_exists('Manufacturer', $object)) {
            // Windows
            $data['type'] = $object['Type'];
            $data['name'] = $object['Name'];
            $data['manufacturer'] = $object['Manufacturer'];
            $data['caption'] = $object['Name'];
            $data['description'] = $object['Comment'];
            $data['version'] = $object['Version'];
        } else {
            // UNIX
            $data['type'] = $object['Version'];
            $data['name'] = $object['Type'];
            $data['manufacturer'] = $object['Name'];
            $data['caption'] = null;
            $data['description'] = null;
            $data['version'] = null;
        }
        return $data;
    }
}
