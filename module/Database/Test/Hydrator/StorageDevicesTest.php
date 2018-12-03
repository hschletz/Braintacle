<?php
/**
 * Tests for StorageDevices hydrator
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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
        $windowsAgentTypePrimary = [
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
        $windowsAgentTypeEmpty = [
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
        $windowsAgentTypeUnknown = [
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
        $windowsAgentRemovableMedia = [
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
        $unixAgent = [
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
        $androidAgent = [
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
        $windowsStorageDevice = [
            'Type' => '_type',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        ];
        $windowsStorageDeviceRemovableMedia = [
            'Type' => '_type',
            'ProductName' => '_productName',
            'Device' => null,
            'Size' => null,
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        ];
        $unixStorageDevice = [
            'ProductFamily' => '_productFamily',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        ];
        $androidStorageDevice = [
            'Type' => '_type',
            'Size' => '42',
        ];
        return [
            [$windowsAgentTypePrimary, $windowsStorageDevice],
            [$windowsAgentTypeEmpty, $windowsStorageDevice],
            [$windowsAgentTypeUnknown, $windowsStorageDevice],
            [$windowsAgentRemovableMedia, $windowsStorageDeviceRemovableMedia],
            [$unixAgent, $unixStorageDevice],
            [$androidAgent, $androidStorageDevice],
        ];
    }

    public function extractProvider()
    {
        $windowsStorageDevice = [
            'Type' => '_type',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        ];
        $unixStorageDevice = [
            'ProductFamily' => '_productFamily',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        ];
        $androidStorageDevice = [
            'Type' => '_type',
            'Size' => '42',
        ];
        $windowsAgent = [
            'manufacturer' => null,
            'name' => '_productName',
            'model' => '_device',
            'type' => '_type',
            'description' => null,
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
        ];
        $unixAgent = [
            'manufacturer' => '_productFamily',
            'name' => '_device',
            'model' => '_productName',
            'type' => null,
            'description' => null,
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
        ];
        $androidAgent = [
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
            [$windowsStorageDevice, $windowsAgent],
            [$unixStorageDevice, $unixAgent],
            [$androidStorageDevice, $androidAgent],
        ];
    }
}
