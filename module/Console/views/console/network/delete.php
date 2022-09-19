<?php

/**
 * Display confirmation form for network device deletion
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
 *
 */

$name = $this->device['Description'];
if (!$name) {
    $name = $this->device['Hostname'];
}
print $this->formYesNo(
    sprintf(
        $this->translate(
            'The network device \'%1s\' with the MAC address %2s will be permanently deleted. Continue?'
        ),
        $this->escapeHtml($name),
        $this->escapeHtml($this->device['MacAddress'])
    )
);
