<?php
/**
 * Hydrator for storage devices
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
 * Hydrator for storage devices
 *
 * Sanitizes incompatible structures produced by different agents. Original
 * values with meaningless content are not preserved and replaced with NULL on
 * extraction.
 */
class StorageDevices implements \Zend\Hydrator\HydratorInterface
{
    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        $object->exchangeArray(array());
        if ($data['is_windows']) {
            // Type is usually stored in 'type'; use 'description' as fallback
            if ($data['type'] == '' or $data['type'] == 'UNKNOWN') {
                $object['Type'] = $data['description'];
            } else {
                $object['Type'] = $data['type'];
            }
            $object['ProductName'] = $data['name'];
            // For removable media, 'model' is identical to 'name' and thus
            // useless. For Hard disks and USB storage, 'model' contains the
            // device path.
            if ($data['model'] == $data['name']) {
                $object['Device'] = null;
            } else {
                $object['Device'] = $data['model'];
            }
        } else {
            $object['ProductFamily'] = $data['manufacturer'];
            $object['ProductName'] = $data['model'];
            $object['Device'] = $data['name'];
        }
        $object['Size'] = ($data['disksize'] == '0') ? null : $data['disksize'];
        $object['Serial'] = $data['serialnumber'];
        $object['Firmware'] = $data['firmware'];

        return $object;
    }

    /** {@inheritdoc} */
    public function extract($object)
    {
        if (array_key_exists('Type', $object)) {
            // Windows
            $data = array(
                'manufacturer' => null,
                'name' => $object['ProductName'],
                'model' => $object['Device'],
                'type' => $object['Type'],
                'description' => null,
            );
        } else {
            // UNIX
            $data = array(
                'manufacturer' => $object['ProductFamily'],
                'name' => $object['Device'],
                'model' => $object['ProductName'],
                'type' => null,
                'description' => null,
            );
        }
        $data['disksize'] = $object['Size'];
        $data['serialnumber'] = $object['Serial'];
        $data['firmware'] = $object['Firmware'];
        return $data;
    }
}
