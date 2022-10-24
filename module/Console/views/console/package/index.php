<?php

/**
 * Display package listing
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

$flashMessenger = $this->flashMessenger();
$name = $flashMessenger->getMessagesFromNamespace('packageName');
$currentPackage = @$name[0];

print $flashMessenger->render('error');

$successMessages = $flashMessenger->getSuccessMessages();
if ($successMessages) {
    print $this->htmlList(
        $successMessages,
        false,
        array('class' => 'success'),
        false
    );
}

// Column headers
$headers = array(
    'Name' => $this->translate('Name'),
    'Timestamp' => $this->translate('Date'),
    'Size' => $this->translate('Size'),
    'Platform' => $this->translate('Platform'),
    'NumPending' => $this->translate('Pending'),
    'NumRunning' => $this->translate('Running'),
    'NumSuccess' => $this->translate('Success'),
    'NumError' => $this->translate('Error'),
    'Delete' => '',
);

$renderNumPackages = function ($view, $package, $property) {
    switch ($property) {
        case 'NumPending':
            $filter = 'PackagePending';
            $class = 'package_pending';
            break;
        case 'NumRunning':
            $filter = 'PackageRunning';
            $class = 'package_running';
            break;
        case 'NumSuccess':
            $filter = 'PackageSuccess';
            $class = 'package_success';
            break;
        case 'NumError':
            $filter = 'PackageError';
            $class = 'package_error';
            break;
    }
    $num = $package[$property];
    if ($num) {
        return $view->htmlElement(
            'a',
            $num,
            array(
                'href' => $view->consoleUrl(
                    'client',
                    'index',
                    array(
                        'columns' => 'Name,UserName,LastContactDate,InventoryDate',
                        'jumpto' => 'software',
                        'filter' => $filter,
                        'search' => $package['Name'],
                    )
                ),
                'class' => $class,
            ),
            true
        );
    } else {
        return $num;
    }
};

$renderCallbacks = array(
    'Name' => function ($view, $package) {
        $attributes = array(
            'href' => $view->consoleUrl(
                'package',
                'update',
                array('name' => $package['Name'])
            ),
        );
        if ($package['Comment']) {
            $attributes['title'] = $package['Comment'];
        }
        return $view->htmlElement(
            'a',
            $view->escapeHtml($package['Name']),
            $attributes,
            true
        );
    },
    'Size' => function ($view, $package) {
        $size = $view->numberFormat(
            $package['Size'] / 1048576,
            \NumberFormatter::DECIMAL,
            \NumberFormatter::TYPE_DOUBLE,
            null,
            1
        );
        $size .= "\xC2\xA0MB";
        return $view->escapeHtml($size);
    },
    'Platform' => function ($view, $package) {
        return $view->escapeHtml(ucfirst($package['Platform']));
    },
    'Delete' => function ($view, $package) {
        return $view->htmlElement(
            'a',
            $view->translate('Delete'),
            array(
                'href' => $view->consoleUrl(
                    'package',
                    'delete',
                    array('name' => $package['Name'])
                ),
            ),
            true
        );
    },
    'NumPending' => $renderNumPackages,
    'NumRunning' => $renderNumPackages,
    'NumSuccess' => $renderNumPackages,
    'NumError' => $renderNumPackages,
);

$rowClassCallback = function ($row) use ($currentPackage) {
    if ($row['Name'] == $currentPackage) {
        return 'highlight';
    } else {
        return null;
    }
};

print $this->table(
    $this->packages,
    $headers,
    $this->sorting,
    $renderCallbacks,
    array(
        'Size' => 'textright',
        'NumPending' => 'textright',
        'NumRunning' => 'textright',
        'NumSuccess' => 'textright',
        'NumError' => 'textright',
    ),
    $rowClassCallback
);
