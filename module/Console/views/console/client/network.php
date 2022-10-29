<?php

/**
 * Display network information
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

// Display global network settings if available
$dnsDomain = $client['DnsDomain'];
$dnsServer = $client['DnsServer'];
$defaultGateway = $client['DefaultGateway'];
$workgroup = $client['Windows'] ? $client['Windows']['Workgroup'] : null;
if ($dnsDomain or $dnsServer or $defaultGateway or $workgroup) {
    print $this->htmlElement('h2', $this->translate('Global network configuration'));
    $table = $this->plugin('table');
    print "<table class='textnormalsize'>\n";
    if ($dnsDomain) {
        print $table->row(
            array(
                $this->translate('Hostname'),
                $this->escapeHtml("$client[Name].$dnsDomain"),
            )
        );
    }
    if ($dnsServer) {
        print $table->row(
            array(
                $this->translate('DNS server'),
                $this->escapeHtml($dnsServer),
            )
        );
    }
    if ($defaultGateway) {
        print $table->row(
            array(
                $this->translate('Default gateway'),
                $this->escapeHtml($defaultGateway),
            )
        );
    }
    if ($workgroup) {
        print $table->row(
            array(
                $this->translate('Workgroup'),
                $this->escapeHtml($workgroup),
            )
        );
    }
    print "</table>\n";
}

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
    'MacAddress' => function ($view, $interface) {
        $macAddress = $interface['MacAddress'];
        $address = $macAddress->getAddress();
        $vendor = $macAddress->getVendor();
        if ($vendor) {
            $address .= " ($vendor)";
        }
        $address = $view->escapeHtml($address);
        if ($interface['IsBlacklisted']) {
            return "<span class='blacklisted'>$address</span>";
        } else {
            return $address;
        }
    }
);

$interfaces = $client['NetworkInterface'];
if (count($interfaces)) {
    print $this->htmlElement(
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

$modems = $client['Modem'];
if (count($modems)) {
    print $this->htmlElement(
        'h2',
        $this->translate('Modems')
    );
    print $this->table($modems, $headers);
}
