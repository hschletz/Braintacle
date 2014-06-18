<?php
/**
 * Display network information
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

// Display global network settings

print "<dl>\n";

print $this->htmlTag(
    'dt',
    $this->translate('DNS server')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($computer['DnsServer'])
);

print $this->htmlTag(
    'dt',
    $this->translate('Default gateway')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($computer['DefaultGateway'])
);

print "</dl>\n";


// Display netwok interfaces if present

$headers = array(
    'Description' => $this->translate('Description'),
    'Rate' => $this->translate('Data rate'),
    'MacAddress' => $this->translate('MAC address'),
    'IpAddress' => $this->translate('IP address'),
    'Netmask' => $this->translate('Netmask'),
    'Gateway' => $this->translate('Gateway'),
    'DhcpServer' => $this->translate('DHCP server'),
    'Status' => $this->translate('Status'),
);

$renderCallbacks = array(
    'MacAddress' => function($view, $interface) {
        $mac = $view->escapeHtml($interface['MacAddress']->getAddressWithVendor());
        if ($interface['IsBlacklisted']) {
            return "<span class='gray'>$mac</span>";
        } else {
            return $mac;
        }
    }
);

$interfaces = $computer['NetworkInterface'];
if (count($interfaces)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Network interfaces')
    );
    print $this->table(
        $interfaces,
        $headers,
        null,
        $renderCallbacks
    );
}


// Display modems if present

$headers = array(
    'Type' => $this->translate('Type'),
    'Name' => $this->translate('Name'),
);

$modems = $computer['Modem'];
if (count($modems)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Modems')
    );
    print $this->table($modems, $headers);
}
