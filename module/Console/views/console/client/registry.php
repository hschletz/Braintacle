<?php

/**
 * Display inventoried registry keys
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
$values = $this->values;

$headers = array(
    'Value' => $this->translate('Value'),
    'Data' => $this->translate('Content'),
);

$renderCallbacks = array(
    'Value' => function ($view, $data) use ($values) {
        return $view->htmlElement(
            'span',
            $view->escapeHtml($data['Value']),
            array('title' => $values[$data['Value']]['FullPath'])
        );
    },
);

$data = $client->getItems('RegistryData', $this->order, $this->direction);
if (count($data)) {
    print $this->htmlElement(
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

print $this->htmlElement(
    'p',
    $this->htmlElement(
        'a',
        $this->translate('Manage inventoried values'),
        array('href' => $this->consoleUrl('preferences', 'registryvalues')),
        true
    ),
    array('class' => 'textcenter')
);
