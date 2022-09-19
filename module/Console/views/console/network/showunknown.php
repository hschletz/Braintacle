<?php

/**
 * Display unknown devices on a subnet.
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

$headers = array(
    'MacAddress' => $this->translate('MAC address'),
    'Vendor' => $this->translate('Vendor'),
    'IpAddress' => $this->translate('IP address'),
    'Hostname' => $this->translate('Hostname'),
    'DiscoveryDate' => $this->translate('Discovery date'),
    'edit' => '',
    'delete' => '',
);

$renderCallbacks = array(
    'Vendor' => function ($view, $device, $property) {
        return $view->escapeHtml($device['MacAddress']->getVendor());
    },
    'edit' => function ($view, $device, $property) {
        return $view->htmlElement(
            'a',
            $view->translate('Edit'),
            array(
                'href' => $view->consoleUrl(
                    'network',
                    'edit',
                    array('macaddress' => $device['MacAddress'])
                ),
            ),
            true
        );
    },
    'delete' => function ($view, $device, $property) {
        return $view->htmlElement(
            'a',
            $view->translate('Delete'),
            array(
                'href' => $view->consoleUrl(
                    'network',
                    'delete',
                    array('macaddress' => $device['MacAddress'])
                ),
            ),
            true
        );
    },
);

print $this->table(
    $this->devices,
    $headers,
    $this->ordering,
    $renderCallbacks
);
