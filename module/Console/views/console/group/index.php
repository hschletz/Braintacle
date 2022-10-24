<?php

/**
 * Display list of groups
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

foreach (array('error', 'success') as $namespace) {
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

$headers = array(
    'Name' => $this->translate('Name'),
    'CreationDate' => $this->translate('Creation date'),
    'Description' => $this->translate('Description'),
);

$renderCallbacks = array(
    'Name' => function ($view, $group) {
        return $view->htmlElement(
            'a',
            $view->escapeHtml($group['Name']),
            array(
                'href' => $view->consoleUrl(
                    'group',
                    'general',
                    array(
                        'name' => $group['Name'],
                    )
                ),
            ),
            true
        );
    },
);

if (count($this->groups)) {
    print $this->table(
        $this->groups,
        $headers,
        $this->sorting,
        $renderCallbacks,
        array('CreationDate' => 'nowrap')
    );
} else {
    print $this->htmlElement(
        'p',
        $this->translate('No groups defined.'),
        array('class' => 'textcenter')
    );
}
