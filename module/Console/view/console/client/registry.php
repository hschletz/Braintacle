<?php
/**
 * Display inventoried registry keys
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

$headers = array(
    'Value.Name' => $this->translate('Key'),
    'Value.ValueInventoried' => $this->translate('Value'),
    'Data' => $this->translate('Content'),
);

$renderCallbacks = array(
    'Value.Name' => function($view, $data) {
        $value = $data['Value'];
        return $view->htmlTag(
            'span',
            $view->escapeHtml($value['Name']),
            array('title' => $value['FullPath'])
        );
    },
    'Value.ValueInventoried' => function($view, $data) {
        return $view->escapeHtml($data['Value']['ValueInventoried']);
    },
);

$data = $client->getItems('RegistryData', $this->order, $this->direction);
if (count($data)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Registry Keys')
    );
    print $this->table(
        $data,
        $headers,
        array('order' => $this->order, 'direction' => $this->direction),
        $renderCallbacks
    );
}

print $this->htmlTag(
    'p',
    $this->htmlTag(
        'a',
        $this->translate('Manage inventoried values'),
        array('href' => $this->ConsoleUrl('preferences', 'registryvalues')),
        true
    ),
    array('class' => 'textcenter')
);
