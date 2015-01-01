<?php
/**
 * Display audio devices, input devices and ports
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

require 'header.php';

$computer = $this->computer;

// Display audio devices
$headers = array(
    'Manufacturer' => $this->translate('Manufacturer'),
    'Name' => $this->translate('Name'),
    'Description' => $this->translate('Description'),
);
$audio = $computer['AudioDevice'];
if (count($audio)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Audio devices')
    );
    print $this->table($audio, $headers);
}

// Display input devices
$headers = array(
    'Type' => $this->translate('Type'),
    'Manufacturer' => $this->translate('Manufacturer'),
    'Description' => $this->translate('Description'),
    'Comment' => $this->translate('Comment'),
    'Interface' => $this->translate('Interface'),
);
$renderCallbacks = array (
    'Type' => function($view, $inputDevice) {
        $type = $inputDevice['Type'];
        switch ($type) {
            case 'Keyboard':
                $type = $view->translate('Keyboard');
                break;
            case 'Pointing':
                $type = $view->translate('Pointing device');
                break;
        }
        return $view->escapeHtml($type);
    },
);
$input = $computer['InputDevice'];
if (count($input)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Input devices')
    );
    print $this->table($input, $headers, null, $renderCallbacks);
}

// Display ports
$ports = $computer['Port'];
if (count($ports)) {
    $headers = array(
        'Type' => $this->translate('Type'),
        'Name' => $this->translate('Name'),
    );
    if (!$computer['Windows']) {
        $headers['Connector'] = $this->translate('Connector');
    }
    print $this->htmlTag(
        'h2',
        $this->translate('Ports')
    );
    print $this->table($ports, $headers);
}
