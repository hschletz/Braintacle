<?php
/**
 * Tests for StorageDevices hydrator
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

namespace Database\Test\Hydrator;

class StorageDevicesTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    public function hydrateProvider()
    {
        $windowsAgentTypePrimary = array(
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_device',
            'type' => '_type',
            'description' => 'ignored',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
        );
        $windowsAgentTypeEmpty = array(
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_device',
            'type' => null,
            'description' => '_type',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
        );
        $windowsAgentTypeUnknown = array(
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_device',
            'type' => 'UNKNOWN',
            'description' => '_type',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
        );
        $windowsAgentRemovableMedia = array(
            'manufacturer' => 'ignored',
            'name' => '_productName',
            'model' => '_productName',
            'type' => '_type',
            'description' => 'ignored',
            'disksize' => '0',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '1',
        );
        $unixAgent = array(
            'manufacturer' => '_productFamily',
            'name' => '_device',
            'model' => '_productName',
            'type' => 'ignored',
            'description' => 'ignored',
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
            'is_windows' => '0',
        );
        $windowsStorageDevice = array(
            'Type' => '_type',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        );
        $windowsStorageDeviceRemovableMedia = array(
            'Type' => '_type',
            'ProductName' => '_productName',
            'Device' => null,
            'Size' => null,
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        );
        $unixStorageDevice = array(
            'ProductFamily' => '_productFamily',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        );
        return array(
            array($windowsAgentTypePrimary, $windowsStorageDevice),
            array($windowsAgentTypeEmpty, $windowsStorageDevice),
            array($windowsAgentTypeUnknown, $windowsStorageDevice),
            array($windowsAgentRemovableMedia, $windowsStorageDeviceRemovableMedia),
            array($unixAgent, $unixStorageDevice),
        );
    }

    public function extractProvider()
    {
        $windowsStorageDevice = array(
            'Type' => '_type',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        );
        $unixStorageDevice = array(
            'ProductFamily' => '_productFamily',
            'ProductName' => '_productName',
            'Device' => '_device',
            'Size' => '42',
            'Serial' => '_serial',
            'Firmware' => '_firmware',
        );
        $windowsAgent = array(
            'manufacturer' => null,
            'name' => '_productName',
            'model' => '_device',
            'type' => '_type',
            'description' => null,
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
        );
        $unixAgent = array(
            'manufacturer' => '_productFamily',
            'name' => '_device',
            'model' => '_productName',
            'type' => null,
            'description' => null,
            'disksize' => '42',
            'serialnumber' => '_serial',
            'firmware' => '_firmware',
        );
        return array(
            array($windowsStorageDevice, $windowsAgent),
            array($unixStorageDevice, $unixAgent),
        );
    }
}
