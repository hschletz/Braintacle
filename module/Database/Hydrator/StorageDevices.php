<?php

/**
 * Hydrator for storage devices
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

namespace Database\Hydrator;

use Model\AbstractModel;

/**
 * Hydrator for storage devices
 *
 * Sanitizes incompatible structures produced by different agents. Original
 * values with meaningless content are not preserved and replaced with NULL on
 * extraction.
 */
class StorageDevices implements \Laminas\Hydrator\HydratorInterface
{
    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        if ($data['is_android']) {
            $object->type = $data['description'];
        } else {
            if ($data['is_windows']) {
                // Type is usually stored in 'type'; use 'description' as fallback
                if ($data['type'] == '' or $data['type'] == 'UNKNOWN') {
                    $object->type = $data['description'];
                } else {
                    $object->type = $data['type'];
                }
                $object->productName = $data['name'];
                // For removable media, 'model' is identical to 'name' and thus
                // useless. For Hard disks and USB storage, 'model' contains the
                // device path.
                if ($data['model'] == $data['name']) {
                    $object->device = null;
                } else {
                    $object->device = $data['model'];
                }
            } else {
                // UNIX
                $object->productFamily = $data['manufacturer'];
                $object->productName = $data['model'];
                $object->device = $data['name'];
            }
            // Windows and UNIX
            $object->firmware = $data['firmware'];
            $object->serial = $data['serialnumber'];
        }
        $object->size = ($data['disksize'] == '0') ? null : $data['disksize'];

        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        if ($object instanceof AbstractModel && $object->offsetExists('Type') || property_exists($object, 'type')) {
            if ($object instanceof AbstractModel && $object->offsetExists('Device') || property_exists($object, 'device')) {
                // Windows
                $data = [
                    'manufacturer' => null,
                    'name' => $object->productName,
                    'model' => $object->device,
                    'type' => $object->type,
                    'description' => null,
                    'serialnumber' => $object->serial,
                    'firmware' => $object->firmware,
                ];
            } else {
                // Android
                $data = [
                    'manufacturer' => null,
                    'name' => null,
                    'model' => null,
                    'type' => null,
                    'description' => $object->type,
                    'serialnumber' => null,
                    'firmware' => null,
                ];
            }
        } else {
            // UNIX
            $data = [
                'manufacturer' => $object->productFamily,
                'name' => $object->device,
                'model' => $object->productName,
                'type' => null,
                'description' => null,
                'serialnumber' => $object->serial,
                'firmware' => $object->firmware,
            ];
        }
        $data['disksize'] = $object->size;
        return $data;
    }
}
