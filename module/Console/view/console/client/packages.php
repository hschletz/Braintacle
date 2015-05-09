<?php
/**
 * Display and install packages
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
    'PackageName' => $this->translate('Name'),
    'Status' => $this->translate('Status'),
    'Timestamp' => $this->translate('Timestamp'),
    'remove' => '',
);

$renderCallbacks = array(
    'Status' => function($view, $assignment) {
        $status = $assignment['Status'];
        switch ($status) {
            case \Model\Package\Assignment::NOT_NOTIFIED:
                $content = $view->translate('not notified');
                $class = 'package_notnotified';
                break;
            case \Model\Package\Assignment::NOTIFIED:
                $content = $view->translate('in progress');
                $class = 'package_inprogress';
                break;
            case \Model\Package\Assignment::SUCCESS:
                $content = $view->translate('installed');
                $class = 'package_success';
                break;
            default: // ERR_*
                $content = $view->escapeHtml($status);
                $class = 'package_error';
        }
        return $view->htmlTag('span', $content, array('class' => $class), true);
    },
    'remove' => function($view, $assignment) {
        return $view->htmlTag(
            'a',
            $view->translate('remove'),
            array(
                'href' => $view->consoleUrl(
                    'client',
                    'removepackage',
                    array(
                        'id' => $view->client['Id'],
                        'package' => $assignment['PackageName'],
                    )
                )
            ),
            true
        );
    },
);

$assignments = $client->getPackages($this->order, $this->direction);
// error_log(var_export($assignments->count(), true));
if (count($assignments)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Assigned packages')
    );
    print $this->table(
        $assignments,
        $headers,
        array('order' => $this->order, 'direction' => $this->direction),
        $renderCallbacks
    );
}

if (isset($this->form)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Install packages')
    );
    print $this->form->render($this);
}
