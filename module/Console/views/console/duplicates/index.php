<?php

/**
 * Display statistics about duplicates
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

foreach (array('error', 'info', 'success') as $namespace) {
    $messages = $this->flashMessenger()->getMessagesFromNamespace($namespace);
    if ($messages) {
        print $this->htmlList(
            $messages,
            false,
            array('class' => $namespace),
            false
        );
    }
}

$labels = array(
    'Name' => $this->translate('Name'),
    'MacAddress' => $this->translate('MAC Address'),
    'Serial' => $this->translate('Serial number'),
    'AssetTag' => $this->translate('Asset tag'),
);

if (count($this->duplicates)) {
    print "<table class='textnormalsize'>\n";
    foreach ($this->duplicates as $type => $num) {
        print '<tr>';
        print $this->htmlElement(
            'td',
            $labels[$type]
        );
        print $this->htmlElement(
            'td',
            $this->htmlElement(
                'a',
                $num,
                array(
                    'href' => $this->consoleUrl('duplicates', 'manage', array('criteria' => $type)),
                )
            ),
            array('class' => 'textright')
        );
        print "</tr>\n";
    }
    print "</table>\n";
} else {
    print '<p class="textcenter">';
    print $this->translate('No duplicates present.');
    print "</p>\n";
}
