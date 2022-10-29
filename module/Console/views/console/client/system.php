<?php

/**
 * Show RAM, controllers and slots
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

print $this->clientHeader($this->client);

$client = $this->client;


// Show memory slots

$headers = array(
    'SlotNumber' => $this->translate('Slot number'),
    'Size' => $this->translate('Size'),
    'Type' => $this->translate('Type'),
    'Clock' => $this->translate('Clock'),
    'Serial' => $this->translate('Serial number'),
    'Caption' => $this->translate('Caption'),
    'Description' => $this->translate('Description'),
);

$renderCallbacks = array (
    'Size' => function ($view, $memorySlot) {
        $size = $view->escapeHtml((string) $memorySlot['Size']);
        if ($size) {
            $size .= '&nbsp;MB';
        }
        return $size;
    },
    'Clock' => function ($view, $memorySlot) {
        $clock = $view->escapeHtml((string) $memorySlot['Clock']);
        if ($clock) {
            $clock .= '&nbsp;MHz';
        }
        return $clock;
    },
);

$memSlots = $client['MemorySlot'];
if (count($memSlots)) {
    print $this->htmlElement(
        'h2',
        $this->translate('Memory slots')
    );
    print $this->table(
        $memSlots,
        $headers,
        null,
        $renderCallbacks
    );
}


// Show controllers

$headers = array();
if ($this->client['Windows'] instanceof \Model\Client\WindowsInstallation) { // Not available for other OS
    $headers['Manufacturer'] = $this->translate('Manufacturer');
}
$headers['Name'] = $this->translate('Name');
$headers['Type'] = $this->translate('Type');

print $this->htmlElement(
    'h2',
    $this->translate('Controllers')
);
print $this->table(
    $client['Controller'],
    $headers
);


// Show extension slots

$headers = array(
    'Name' => $this->translate('Name'),
    'Description' => $this->translate('Description'),
    'Status' => $this->translate('Status'),
);

$renderCallbacks = array (
    'Name' => function ($view, $slot) {
        $name = $slot['Name'];
        if (isset($slot['SlotId'])) {
            $name .= " (#$slot[SlotId])";
        }
        return $view->escapeHtml($name);
    }
);

$extSlots = $client['ExtensionSlot'];
if (count($extSlots)) {
    print $this->htmlElement(
        'h2',
        $this->translate('Extension slots')
    );
    print $this->table(
        $extSlots,
        $headers,
        null,
        $renderCallbacks
    );
}
