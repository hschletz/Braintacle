<?php
/**
 * Display general information about a client
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

$client = $this->client;

print "<dl>\n";

print $this->htmlTag(
    'dt',
    $this->translate('ID')
);
print $this->htmlTag(
    'dd',
    $client['Id']
);

print $this->htmlTag(
    'dt',
    $this->translate('Client ID')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['ClientId'])
);

print $this->htmlTag(
    'dt',
    $this->translate('Inventory date')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['InventoryDate'])
);

print $this->htmlTag(
    'dt',
    $this->translate('Last contact')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['LastContactDate'])
);

print $this->htmlTag(
    'dt',
    $this->translate('User Agent')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['OcsAgent'])
);

print $this->htmlTag(
    'dt',
    $this->translate('Model')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['Manufacturer'] . ' ' . $client['Model'])
);

print $this->htmlTag(
    'dt',
    $this->translate('Serial number')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['Serial']),
    $client['IsSerialBlacklisted'] ? array('class' => 'blacklisted') : null
);

print $this->htmlTag(
    'dt',
    $this->translate('Asset tag')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['AssetTag']),
    $client['IsAssetTagBlacklisted'] ? array('class' => 'blacklisted') : null
);

print $this->htmlTag(
    'dt',
    $this->translate('Type')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['Type'])
);

print $this->htmlTag(
    'dt',
    $this->translate('Operating System')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml(
        sprintf(
            '%s %s (%s)',
            $client['OsName'],
            $client['OsVersionString'],
            $client['OsVersionNumber']
        )
    )
);

print $this->htmlTag(
    'dt',
    $this->translate('Comment')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['OsComment'])
);

print $this->htmlTag(
    'dt',
    $this->translate('CPU type')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['CpuType'])
);

print $this->htmlTag(
    'dt',
    $this->translate('CPU clock')
);
print $this->htmlTag(
    'dd',
    $client['CpuClock'] . '&nbsp;MHz'
);

print $this->htmlTag(
    'dt',
    $this->translate('Number of CPU cores')
);
print $this->htmlTag(
    'dd',
    $client['CpuCores']
);

$physicalRam = 0;
foreach ($client['MemorySlot'] as $slot) {
    $physicalRam += $slot['Size'];
}
print $this->htmlTag(
    'dt',
    $this->translate('RAM detected by agent')
);
print $this->htmlTag(
    'dd',
    $physicalRam . '&nbsp;MB'
);

print $this->htmlTag(
    'dt',
    $this->translate('RAM reported by OS')
);
print $this->htmlTag(
    'dd',
    $client['PhysicalMemory'] . '&nbsp;MB'
);

print $this->htmlTag(
    'dt',
    $this->translate('Swap memory')
);
print $this->htmlTag(
    'dd',
    $client['SwapMemory'] . '&nbsp;MB'
);

$user = $client['UserName'];
$domain = $client['Windows']['UserDomain'];
if ($domain) {
    $user .= ' @ ' . $domain;
}
print $this->htmlTag(
    'dt',
    $this->translate('Last user logged in')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($user)
);

if ($client['Uuid']) {
    print $this->htmlTag(
        'dt',
        $this->translate('UUID')
    );
    print $this->htmlTag(
        'dd',
        $this->escapeHtml($client['Uuid'])
    );
}

print "</dl>\n";
