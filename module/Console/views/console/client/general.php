<?php

/**
 * Display general information about a client
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
$table = $this->plugin('table');
$classes = array('label');

$os = $this->escapeHtml(
    sprintf(
        '%s %s (%s)',
        $client['OsName'],
        $client['OsVersionString'],
        $client['OsVersionNumber']
    )
);
if (isset($client['Windows']['CpuArchitecture'])) {
    $os .= ' &ndash; ' . $this->escapeHtml($client['Windows']['CpuArchitecture']);
}

$physicalRam = 0;
foreach ($client['MemorySlot'] as $slot) {
    $physicalRam += $slot['Size'];
}

$user = $client['UserName'];
if ($client['Windows'] and $client['Windows']['UserDomain']) {
    $user .= ' @ ' . $client['Windows']['UserDomain'];
}

print "<table class='topspacing textnormalsize'>\n";

print $table->row(
    array($this->translate('ID'), $client['Id']),
    false,
    $classes
);
print $table->row(
    array($this->translate('ID string'), $this->escapeHtml($client['IdString'])),
    false,
    $classes
);
print $table->row(
    array(
        $this->translate('Inventory date'),
        $this->dateFormat($client['InventoryDate'], \IntlDateFormatter::FULL, \IntlDateFormatter::LONG)
    ),
    false,
    $classes
);
print $table->row(
    array(
        $this->translate('Last contact'),
        $this->dateFormat($client['LastContactDate'], \IntlDateFormatter::FULL, \IntlDateFormatter::LONG)
    ),
    false,
    $classes
);
print $table->row(
    array($this->translate('User Agent'), $this->escapeHtml($client['UserAgent'])),
    false,
    $classes
);
print $table->row(
    array($this->translate('Model'), $this->escapeHtml($client['Manufacturer'] . ' ' . $client['ProductName'])),
    false,
    $classes
);
print $table->row(
    array($this->translate('Serial number'), $this->escapeHtml($client['Serial'])),
    false,
    $client['IsSerialBlacklisted'] ? $classes + array(1 => 'blacklisted') : $classes
);
print $table->row(
    array($this->translate('Asset tag'), $this->escapeHtml($client['AssetTag'])),
    false,
    $client['IsAssetTagBlacklisted'] ? $classes + array(1 => 'blacklisted') : $classes
);
print $table->row(
    array($this->translate('Type'), $this->escapeHtml($client['Type'])),
    false,
    $classes
);
print $table->row(
    array($this->translate('Operating System'), $os),
    false,
    $classes
);
print $table->row(
    array($this->translate('Comment'), $this->escapeHtml($client['OsComment'])),
    false,
    $classes
);
print $table->row(
    array($this->translate('CPU type'), $this->escapeHtml($client['CpuType'])),
    false,
    $classes
);
print $table->row(
    array($this->translate('CPU clock'), $client['CpuClock'] . '&nbsp;MHz'),
    false,
    $classes
);
print $table->row(
    array($this->translate('Number of CPU cores'), $client['CpuCores']),
    false,
    $classes
);
print $table->row(
    array($this->translate('RAM detected by agent'), $physicalRam . '&nbsp;MB'),
    false,
    $classes
);
print $table->row(
    array($this->translate('RAM reported by OS'), $client['PhysicalMemory'] . '&nbsp;MB'),
    false,
    $classes
);
print $table->row(
    array($this->translate('Swap memory'), $client['SwapMemory'] . '&nbsp;MB'),
    false,
    $classes
);
print $table->row(
    array($this->translate('Last user logged in'), $this->escapeHtml($user)),
    false,
    $classes
);
if ($client['Uuid']) {
    print $table->row(
        array($this->translate('UUID'), $this->escapeHtml($client['Uuid'])),
        false,
        $classes
    );
}

print "</table>\n";
