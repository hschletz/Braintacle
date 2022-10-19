<?php

/**
 * Tests for Controllers hydrator
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

class ControllersTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    public function hydrateProvider()
    {
        $windowsAgent = array(
            'type' => '_type',
            'name' => '_name',
            'manufacturer' => '_manufacturer',
            'caption' => 'ignored',
            'description' => '_comment',
            'version' => '_version',
            'is_windows' => '1',
        );
        $unixAgent = array(
            'type' => '_version',
            'name' => '_type',
            'manufacturer' => '_name',
            'caption' => 'ignored',
            'description' => 'ignored',
            'version' => 'ignored',
            'is_windows' => '0',
        );
        $windowsController = array(
            'Type' => '_type',
            'Name' => '_name',
            'Version' => '_version',
            'Manufacturer' => '_manufacturer',
            'Comment' => '_comment',
        );
        $unixController = array(
            'Type' => '_type',
            'Name' => '_name',
            'Version' => '_version',
        );
        return array(
            array($windowsAgent, $windowsController),
            array($unixAgent, $unixController),
        );
    }

    public function extractProvider()
    {
        $windowsHydrated = [
            'type' => '_type',
            'name' => '_name',
            'version' => '_version',
            'manufacturer' => '_manufacturer',
            'comment' => '_comment',
        ];
        $windowsManufacturerNullHydrated = [
            'type' => '_type',
            'name' => '_name',
            'version' => '_version',
            'manufacturer' => null,
            'comment' => '_comment',
        ];
        $unixHydrated = [
            'type' => '_type',
            'name' => '_name',
            'version' => '_version',
        ];
        $windowsExtracted = [
            'type' => '_type',
            'name' => '_name',
            'manufacturer' => '_manufacturer',
            'caption' => '_name',
            'description' => '_comment',
            'version' => '_version',
        ];
        $windowsManufacturerNullExtracted = [
            'type' => '_type',
            'name' => '_name',
            'manufacturer' => null,
            'caption' => '_name',
            'description' => '_comment',
            'version' => '_version',
        ];
        $unixExtracted = [
            'type' => '_version',
            'name' => '_type',
            'manufacturer' => '_name',
            'caption' => null,
            'description' => null,
            'version' => null,
        ];
        return [
            [$windowsHydrated, $windowsExtracted],
            [$windowsManufacturerNullHydrated, $windowsManufacturerNullExtracted],
            [$unixHydrated, $unixExtracted],
        ];
    }
}
