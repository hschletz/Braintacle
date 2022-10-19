<?php

/**
 * Tests for ExtensionSlot hydrator
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

class ExtensionSlotsTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    public function hydrateProvider()
    {
        $windowsOldAgent = array(
            'name' => 'ignored',
            'description' => '_description',
            'designation' => '_name',
            'purpose' => '',
            'status' => '_status',
            'is_windows' => '1',
        );
        $windowsNewAgent = array(
            'name' => 'ignored',
            'description' => '_description',
            'designation' => '_name',
            'purpose' => '_status',
            'status' => 'ignored',
            'is_windows' => '1',
        );
        $unixAgent = array(
            'name' => '_name',
            'description' => '_description',
            'designation' => '_slotId',
            'purpose' => 'ignored',
            'status' => '_status',
            'is_windows' => '0',
        );
        $windowsSlot = array(
            'Name' => '_name',
            'Description' => '_description',
            'Status' => '_status',
        );
        $unixSlot = array(
            'Name' => '_name',
            'Description' => '_description',
            'Status' => '_status',
            'SlotId' => '_slotId',
        );
        return array(
            array($windowsOldAgent, $windowsSlot),
            array($windowsNewAgent, $windowsSlot),
            array($unixAgent, $unixSlot),
        );
    }

    public function extractProvider()
    {
        $windowsHydrated = [
            'name' => '_name',
            'description' => '_description',
            'status' => '_status',
        ];
        $unixHydrated = [
            'name' => '_name',
            'description' => '_description',
            'status' => '_status',
            'slotId' => '_slotId',
        ];
        $unixSlotIdNullHydrated = [
            'name' => '_name',
            'description' => '_description',
            'status' => '_status',
            'slotId' => null,
        ];
        $windowsExtracted = [
            'name' => '_name',
            'description' => '_description',
            'designation' => '_name',
            'purpose' => '_status',
            'status' => null,
        ];
        $unixExtracted = [
            'name' => '_name',
            'description' => '_description',
            'designation' => '_slotId',
            'purpose' => null,
            'status' => '_status',
        ];
        $unixIdNullExtracted = [
            'name' => '_name',
            'description' => '_description',
            'designation' => null,
            'purpose' => null,
            'status' => '_status',
        ];
        return [
            [$windowsHydrated, $windowsExtracted],
            [$unixHydrated, $unixExtracted],
            [$unixSlotIdNullHydrated, $unixIdNullExtracted],
        ];
    }
}
