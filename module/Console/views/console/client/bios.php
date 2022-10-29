<?php

/**
 * Display BIOS/UEFI information
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
$version = strtr($client['BiosVersion'], ';', "\n");

print "<table class='topspacing textnormalsize'>\n";

print $table->row(
    array($this->translate('Manufacturer'), $this->escapeHtml($client['BiosManufacturer'])),
    false,
    $classes
);
print $table->row(
    array($this->translate('Date'), $this->escapeHtml($client['BiosDate'])),
    false,
    $classes
);
print $table->row(
    array($this->translate('Version'), nl2br($this->escapeHtml($version), $this->doctype()->isXhtml())),
    false,
    $classes
);

print "</table>\n";
