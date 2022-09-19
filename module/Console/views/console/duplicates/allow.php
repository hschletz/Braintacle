<?php

/**
 * Display confirmation form to allow duplicates for given criteria
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

$messages = array(
    'MacAddress' => $this->translate('Exclude MAC address %s from duplicates search?'),
    'Serial' => $this->translate('Exclude serial number \'%s\' from duplicates search?'),
    'AssetTag' => $this->translate('Exclude asset tag \'%s\' from duplicates search?'),
);
print $this->formYesNo(
    sprintf(
        $messages[$this->criteria],
        $this->escapeHtml($this->value)
    )
);
