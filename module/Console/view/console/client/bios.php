<?php
/**
 * Display BIOS/UEFI information
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
    $this->translate('Manufacturer')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['BiosManufacturer'])
);

print $this->htmlTag(
    'dt',
    $this->translate('Date')
);
print $this->htmlTag(
    'dd',
    $this->escapeHtml($client['BiosDate'])
);

$version = strtr($client['BiosVersion'], ';', "\n");
print $this->htmlTag(
    'dt',
    $this->translate('Version')
);
print $this->htmlTag(
    'dd',
    nl2br($this->escapeHtml($version), $this->doctype()->isXhtml())
);

print "</dl>\n";
