<?php

/**
 * Display and manage users
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

$headers = array(
    'Id' => $this->translate('Login name'),
    'FirstName' => $this->translate('First name'),
    'LastName' => $this->translate('Last name'),
    'MailAddress' => $this->translate('Mail address'),
    'edit' => '',
    'delete' => '',
);

$renderCallbacks = array(
    'MailAddress' => function ($view, $account) {
        $address = $account['MailAddress'];
        if ($address) {
            return $view->htmlElement(
                'a',
                $view->escapeHtml($address),
                array(
                    'href' => 'mailto:' . $view->escapeUrl($address),
                ),
                true
            );
        }
    },
    'edit' => function ($view, $account) {
        return $view->htmlElement(
            'a',
            $view->translate('Edit'),
            array(
                'href' => $view->consoleUrl(
                    'accounts',
                    'edit',
                    array(
                        'id' => $account['Id'],
                    )
                )
            ),
            true
        );
    },
    'delete' => function ($view, $account) {
        if ($account['Id'] == $this->identity()) {
            return '';
        } else {
            return $view->htmlElement(
                'a',
                $view->translate('Delete'),
                array(
                    'href' => $view->consoleUrl(
                        'accounts',
                        'delete',
                        array(
                            'id' => $account['Id'],
                        )
                    )
                ),
                true
            );
        }
    }
);

print $this->table(
    $this->accounts,
    $headers,
    $this->vars(),
    $renderCallbacks
);

print $this->htmlElement(
    'p',
    $this->htmlElement(
        'a',
        $this->translate('Add user'),
        array('href' => $this->consoleUrl('accounts', 'add'))
    ),
    array('class' => 'textcenter')
);
