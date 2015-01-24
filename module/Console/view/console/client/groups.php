<?php
/**
 * Display and manage a client's group memberships
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
    'GroupName' => $this->translate('Group'),
    'Membership' => $this->translate('Membership'),
);

$renderCallbacks = array(
    'GroupName' => function($view, $membership) {
        return $view->htmlTag(
            'a',
            $view->escapeHtml($membership['GroupName']),
            array(
                'href' => $view->consoleUrl(
                    'group',
                    'general',
                    array('name' => $membership['GroupName'])
                )
            ),
            true
        );
    },
    'Membership' => function($view, $membership) {
        return $view->membershipType($membership['Membership']);
    },
);

$memberships = array();
foreach ($this->memberships as $membership) {
    if ($membership['Membership'] != \Model_GroupMembership::TYPE_EXCLUDED) {
        $memberships[] = $membership;
    }
}
if (count($memberships)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Group memberships')
    );
    print $this->table(
        $memberships,
        $headers,
        array('order' => $this->order, 'direction' => $this->direction),
        $renderCallbacks
    );
}

if (isset($this->form)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Manage memberships')
    );
    print $this->form->render($this);
}
