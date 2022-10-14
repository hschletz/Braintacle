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
        $object->exchangeArray([]);
        if ($data['is_android']) {
            $object->Type = $data['description'];
        } else {
            if ($data['is_windows']) {
                // Type is usually stored in 'type'; use 'description' as fallback
                if ($data['type'] == '' or $data['type'] == 'UNKNOWN') {
                    $object->Type = $data['description'];
                } else {
                    $object->Type = $data['type'];
                }
                $object->ProductName = $data['name'];
                // For removable media, 'model' is identical to 'name' and thus
                // useless. For Hard disks and USB storage, 'model' contains the
                // device path.
                if ($data['model'] == $data['name']) {
                    $object->Device = null;
                } else {
                    $object->Device = $data['model'];
                }
            } else {
                // UNIX
                $object->ProductFamily = $data['manufacturer'];
                $object->ProductName = $data['model'];
                $object->Device = $data['name'];
            }
            // Windows and UNIX
            $object->Firmware = $data['firmware'];
            $object->Serial = $data['serialnumber'];
        }
        $object->Size = ($data['disksize'] == '0') ? null : $data['disksize'];

        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        if (property_exists($object, 'Type')) {
            if (property_exists($object, 'Device')) {
                // Windows
                $data = [
                    'manufacturer' => null,
                    'name' => $object->ProductName,
                    'model' => $object->Device,
                    'type' => $object->Type,
                    'description' => null,
                    'serialnumber' => $object->Serial,
                    'firmware' => $object->Firmware,
                ];
            } else {
                // Android
                $data = [
                    'manufacturer' => null,
                    'name' => null,
                    'model' => null,
                    'type' => null,
                    'description' => $object->Type,
                    'serialnumber' => null,
                    'firmware' => null,
                ];
            }
        } else {
            // UNIX
            $data = [
                'manufacturer' => $object->ProductFamily,
                'name' => $object->Device,
                'model' => $object->ProductName,
                'type' => null,
                'description' => null,
                'serialnumber' => $object->Serial,
                'firmware' => $object->Firmware,
            ];
        }
        $data['disksize'] = $object->Size;
        return $data;
    }
}
