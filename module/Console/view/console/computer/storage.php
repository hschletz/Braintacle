<?php
/**
 * Display storage information
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


// Display storage devices

$headers = array(
    'Type' => $this->translate('Type'),
    'Name' => $this->translate('Name'),
    'Size' => $this->translate('Size'),
);

$renderSize = function($view, $object, $property) {
    $size = $object[$property];
    if ($object['Size']) { // Ignore objects with no total size
        $output = $view->numberFormat($size / 1024, \NumberFormatter::DECIMAL, \NumberFormatter::TYPE_DOUBLE, null, 1);
        $output .= "\xC2\xA0GB";
        if ($property != 'Size') {
            $output .= sprintf(
                ' (%s)',
                $view->numberFormat(
                    $size / $object['Size'], \NumberFormatter::PERCENT, \NumberFormatter::TYPE_DOUBLE, null, 0
                )
            );
        }
        return $view->escapeHtml($output);
    }
};

$renderCallbacks = array('Size' => $renderSize);

if ($computer['Windows']) {
    $renderCallbacks['Type'] = function($view, $computer, $property) {
        $type = $computer['Type'];
        // Some generic device types can be translated.
        switch ($type) {
            case 'DVD Writer':
                $type = $view->translate('DVD writer');
                break;
            case 'Hard disk':
                $type = $view->translate('Hard disk');
                break;
            case 'Removable medium':
                $type = $view->translate('Removable medium');
                break;
            case 'Floppy disk drive':
                $type = $view->translate('Floppy disk drive');
                break;
        }
        return $view->escapeHtml($type);
    };
} else {
    // Additional Columns for UNIX systems
    $headers['Device'] = $this->translate('Device');
    $headers['Serial'] = $this->translate('Serial number');
    $headers['Firmware'] = $this->translate('Firmware version');
}

print $this->htmlTag(
    'h2',
    $this->translate('Storage devices')
);
print $this->table(
    $computer['StorageDevice'],
    $headers,
    null,
    $renderCallbacks
);


// Display volumes

if ($computer['Windows']) {
    $headers = array(
        'Letter' => $this->translate('Letter'),
        'Label' => $this->translate('Label'),
        'Type' => $this->translate('Type'),
        'Filesystem' => $this->translate('Filesystem'),
        'Size' => $this->translate('Size'),
        'UsedSpace' => $this->translate('Used space'),
        'FreeSpace' => $this->translate('Free space'),
    );
} else {
    $headers = array(
        'Mountpoint' => $this->translate('Mountpoint'),
        'Device' => $this->translate('Device'),
        'Filesystem' => $this->translate('Filesystem'),
        'CreationDate' => $this->translate('Creation date'),
        'Size' => $this->translate('Size'),
        'UsedSpace' => $this->translate('Used space'),
        'FreeSpace' => $this->translate('Free space'),
    );
}

$renderCallbacks = array(
    'Size' => $renderSize,
    'UsedSpace' => $renderSize,
    'FreeSpace' => $renderSize,
    'CreationDate' => function ($view, $volume) {
        $date = $volume['CreationDate'];
        if ($date) {
            return $view->escapeHtml($date->get(\Zend_Date::DATE_MEDIUM));
        }
    }
);

print $this->htmlTag(
    'h2',
    $this->translate('Volumes')
);
print $this->table(
    $computer['Volume'],
    $headers,
    null,
    $renderCallbacks
);
