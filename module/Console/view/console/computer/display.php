<?php
/**
 * Show display information
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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


// Show display controllers

$headers = array(
    'Name' => $this->translate('Name'),
    'Chipset' => $this->translate('Chipset'),
    'Memory' => $this->translate('Memory'),
    'CurrentResolution' => $this->translate('Current resolution'),
);

$renderValues = function($view, $object, $property) {
};

$renderCallbacks = array (
    'Memory' => function($view, $displayController) {
        $value = $view->escapeHtml($displayController['Memory']);
        if ($value) {
            $value .= '&nbsp;MB';
        }
        return $value;
    },
);

print $this->htmlTag(
    'h2',
    $this->translate('Display controllers')
);
print $this->table(
    $computer['DisplayController'],
    $headers,
    null,
    $renderCallbacks
);


// Show display devices

$displays = $computer['Display'];
if (count($displays)) {
    $headers = array(
        'Manufacturer' => $this->translate('Manufacturer'),
        'Description' => $this->translate('Description'),
        'Serial' => $this->translate('Serial number'),
        'ProductionDate' => $this->translate('Production date'),
        'Type' => $this->translate('Type'),
    );

    print $this->htmlTag(
        'h2',
        $this->translate('Displays')
    );
    print $this->table(
        $computer['Display'],
        $headers
    );
}
