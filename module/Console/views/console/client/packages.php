<?php
/**
 * Display and assign packages
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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
    'reset' => '',
    'remove' => '',
);

$renderCallbacks = array(
    'Status' => function ($view, $assignment) {
        $status = $assignment['Status'];
        switch ($status) {
            case \Model\Package\Assignment::PENDING:
                $content = $view->translate('Pending');
                $class = 'package_pending';
                break;
            case \Model\Package\Assignment::RUNNING:
                $content = $view->translate('Running');
                $class = 'package_running';
                break;
            case \Model\Package\Assignment::SUCCESS:
                $content = $view->translate('Success');
                $class = 'package_success';
                break;
            default: // ERR_*
                $content = $view->escapeHtml($status);
                $class = 'package_error';
        }
        return $view->htmlElement('span', $content, array('class' => $class), true);
    },
    'reset' => function ($view, $assignment) {
        if ($assignment['Status'] != \Model\Package\Assignment::PENDING) {
            return $view->htmlElement(
                'a',
                $view->translate('reset'),
                array(
                    'href' => $view->consoleUrl(
                        'client',
                        'resetpackage',
                        array(
                            'id' => $view->client['Id'],
                            'package' => $assignment['PackageName'],
                        )
                    )
                ),
                true
            );
        } else {
            return '';
        }
    },
    'remove' => function ($view, $assignment) {
        return $view->htmlElement(
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

$assignments = $client->getPackageAssignments($this->order, $this->direction);
if (count($assignments)) {
    print $this->htmlElement(
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
    print $this->htmlElement(
        'h2',
        $this->translate('Assign packages')
    );
    print $this->form->render($this);
}
