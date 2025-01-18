<?php

/**
 * Display statistics about duplicates
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Duplicates\Criterion;
use Braintacle\Http\RouteHelper;

/** @var RouteHelper */
$routeHelper = $this->routeHelper;

if ($this->message) {
    echo $this->htmlElement('p', $this->message, ['class' => 'success']);
}

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

if (count($this->duplicates)) {
    print "<table class='textnormalsize'>\n";
    foreach ($this->duplicates as $criterion => $num) {
        print '<tr>';
        print $this->htmlElement(
            'td',
            match (Criterion::from($criterion)) {
                Criterion::Name => $this->translate('Name'),
                Criterion::MacAddress => $this->translate('MAC Address'),
                Criterion::Serial => $this->translate('Serial number'),
                Criterion::AssetTag => $this->translate('Asset tag'),
            }
        );
        print $this->htmlElement(
            'td',
            $this->htmlElement(
                'a',
                $num,
                ['href' => $routeHelper->getPathForRoute('manageDuplicates', ['criterion' => $criterion])],
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
