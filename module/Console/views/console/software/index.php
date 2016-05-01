<?php
/**
 * Display list of all software
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

$nameFilter = new \Library\Filter\FixEncodingErrors;

$headers = array(
    'AcceptIgnore' => '',
    'name' => $this->translate('Name'),
    'num_clients' => $this->translate('Count'),
);

$renderCallbacks = array (
    'AcceptIgnore' => function ($view, $software) {
        $links = array();
        if ($view->filter == 'ignored' or $view->filter == 'new') {
            $links[] = $view->htmlElement(
                'a',
                $view->translate('Accept'),
                array(
                    'href' => $view->consoleUrl(
                        'software',
                        'accept',
                        array('name' => $software['name'])
                    ),
                ),
                true
            );
        }
        if ($view->filter == 'accepted' or $view->filter == 'new') {
            $links[] = $view->htmlElement(
                'a',
                $view->translate('Ignore'),
                array(
                    'href' => $view->consoleUrl(
                        'software',
                        'ignore',
                        array('name' => $software['name'])
                    ),
                ),
                true
            );
        }
        return implode(' ', $links);
    },
    'name' => function ($view, $software) use ($nameFilter) {
        return $view->escapeHtml($nameFilter->filter($software['name']));
    },
    'num_clients' => function ($view, $software) {
        return $view->htmlElement(
            'a',
            $software['num_clients'],
            array(
                'href' => $view->consoleUrl(
                    'client',
                    'index',
                    array(
                        'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                        'jumpto' => 'software',
                        'filter' => 'Software',
                        'search' => $software['name'],
                    )
                ),
            ),
            true
        );
    }
);

print $this->form->render($this);
print $this->table(
    $this->software,
    $headers,
    $this->order,
    $renderCallbacks,
    array('num_clients' => 'textright')
);
