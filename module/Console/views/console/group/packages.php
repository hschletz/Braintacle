<?php
/**
 * Display assigned and installable packages
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

require('header.php');

$headers = array(
    'Name' => $this->translate('Name'),
    'remove' => '',
);

$renderCallbacks = array(
    'Name' => function ($view, $packageName) {
        // Use callback because default renderer only operates on array row data
        return $view->escapeHtml($packageName);
    },
    'remove' => function ($view, $packageName) {
        return $view->htmlElement(
            'a',
            $view->translate('remove'),
            array(
                'href' => $view->consoleUrl(
                    'group',
                    'removepackage',
                    array(
                        'package' => $packageName,
                        'name' => $view->group['Name'],
                    )
                ),
            ),
            true
        );
    }
);

if (count($this->packageNames)) {
    print $this->htmlElement(
        'h2',
        $this->translate('Assigned packages')
    );
    print $this->table(
        $this->packageNames,
        $headers,
        $this->sorting,
        $renderCallbacks
    );
}

if (isset($this->form)) {
    print $this->htmlElement(
        'h2',
        $this->translate('Assign packages')
    );
    print $this->form->render($this);
}
