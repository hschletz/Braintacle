<?php

/**
 * Display all excluded clients
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

print $this->groupHeader($group);

$headers = array(
    'Name' => $this->translate('Name'),
    'UserName' => $this->translate('User'),
    'InventoryDate' => $this->translate('Last inventory'),
);

$renderCallbacks = array(
    'Name' => function ($view, $client) {
        return $view->htmlElement(
            'a',
            $view->escapeHtml($client['Name']),
            array(
                'href' => $view->consoleUrl(
                    'client',
                    'groups',
                    array('id' => $client['Id'])
                ),
            ),
            true
        );
    }
);

print $this->htmlElement(
    'p',
    sprintf(
        $this->translate('Number of clients: %d'),
        count($this->clients)
    ),
    array('class' => 'textcenter')
);

print $this->table(
    $this->clients,
    $headers,
    $this->sorting,
    $renderCallbacks
);
