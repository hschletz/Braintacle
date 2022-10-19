<?php

/**
 * Tests for StorageDevices hydrator
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

namespace Database\Test\Hydrator;

class StorageDevicesTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    public function hydrateProvider()
    {
        $windowsTypePrimaryExtracted = [
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_device',
            'type' => '_type',
            'description' => 'ignored',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
            'is_android' => '0',
        ];
        $windowsTypeEmptyExtracted = [
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_device',
            'type' => null,
            'description' => '_type',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
            'is_android' => '0',
        ];
        $windowsTypeUnknownExtracted = [
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_device',
            'type' => 'UNKNOWN',
            'description' => '_type',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
            'is_android' => '0',
        ];
        $windowsRemovableMediaExtracted = [
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_productName',
            'type' => '_type',
            'description' => 'ignored',
            'disksize' => '0',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
            'is_android' => '0',
        ];
        $unixExtracted = [
            'manufacturer' => '_productFamily',
            'name' => '_device',
            'model' => '_productName',
            'type' => 'ignored',
            'description' => 'ignored',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '0',
            'is_android' => '0',
        ];
        $androidExtracted = [
            'manufacturer' => 'ignored',
            'name' => 'ignored',
            'model' => 'ignored',
            'type' => 'ignored',
            'description' => '_type',
            'disksize' => '42',
            'serialnumber' => 'ignored',
            'firmware' => 'ignored',
            'is_windows' => '0',
            'is_android' => '1',
        ];
        $windowsHydrated = [
            'type' => '_type',
            'productName' => '_productName',
            'device' => '_device',
            'size' => '42',
            'serial' => '_serial',
            'firmware' => '_firmware',
        ];
        $windowsRemovableMediaHydrated = [
            'type' => '_type',
            'productName' => '_productName',
            'device' => null,
            'size' => null,
            'serial' => '_serial',
            'firmware' => '_firmware',
        ];
        $unixHydrated = [
            'productFamily' => '_productFamily',
            'productName' => '_productName',
            'device' => '_device',
            'size' => '42',
            'serial' => '_serial',
            'firmware' => '_firmware',
        ];
        $androidHydrated = [
            'type' => '_type',
            'size' => '42',
        ];
        return [
            [$windowsTypePrimaryExtracted, $windowsHydrated],
            [$windowsTypeEmptyExtracted, $windowsHydrated],
            [$windowsTypeUnknownExtracted, $windowsHydrated],
            [$windowsRemovableMediaExtracted, $windowsRemovableMediaHydrated],
            [$unixExtracted, $unixHydrated],
            [$androidExtracted, $androidHydrated],
        ];
    }

    public function extractProvider()
    {
        $windowsHydrated = [
            'type' => '_type',
            'productName' => '_productName',
            'device' => '_device',
            'size' => '42',
            'serial' => '_serial',
            'firmware' => '_firmware',
        ];
        $unixHydrated = [
            'productFamily' => '_productFamily',
            'productName' => '_productName',
            'device' => '_device',
            'size' => '42',
            'serial' => '_serial',
            'firmware' => '_firmware',
        ];
        $androidHydrated = [
            'type' => '_type',
            'size' => '42',
        ];
        $windowsExtracted = [
            'manufacturer' => null,
            'name' => '_productName',
            'model' => '_device',
            'type' => '_type',
            'description' => null,
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
        ];
        $unixExtracted = [
            'manufacturer' => '_productFamily',
            'name' => '_device',
            'model' => '_productName',
            'type' => null,
            'description' => null,
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
        ];
        $androidExtracted = [
            'manufacturer' => null,
            'name' => null,
            'model' => null,
            'type' => null,
            'description' => '_type',
            'disksize' => '42',
            'serialnumber' => null,
            'firmware' => null,
        ];
        return [
            [$windowsHydrated, $windowsExtracted],
            [$unixHydrated, $unixExtracted],
            [$androidHydrated, $androidExtracted],
        ];
    }
}
