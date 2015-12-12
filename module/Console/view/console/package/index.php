<?php
/**
 * Display package listing
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
 *
 */

$flashMessenger = $this->flashMessenger();
$name = $flashMessenger->getMessagesFromNamespace('packageName');
$currentPackage = @$name[0];

// Error messages are not translated. FormatMessages helper is not affected.
$flashMessenger->setTranslator(null);
print $flashMessenger->render('error');

$successMessages = $flashMessenger->getSuccessMessages();
if ($successMessages) {
    print $this->htmlList(
        $this->formatMessages($successMessages),
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
    'NumNonnotified' => $this->translate('Not notified'),
    'NumSuccess' => $this->translate('Success'),
    'NumNotified' => $this->translate('Running'),
    'NumError' => $this->translate('Error'),
    'Delete' => '',
);

$renderNumPackages = function ($view, $package, $property) {
    switch ($property) {
        case 'NumNonnotified':
            $filter = 'PackageNonnotified';
            $class = 'package_notnotified';
            break;
        case 'NumSuccess':
            $filter = 'PackageSuccess';
            $class = 'package_success';
            break;
        case 'NumNotified':
            $filter = 'PackageNotified';
            $class = 'package_inprogress';
            break;
        case 'NumError':
            $filter = 'PackageError';
            $class = 'package_error';
            break;
    }
    $num = $package[$property];
    if ($num) {
        return $view->htmlTag(
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
        return $view->htmlTag(
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
        return $view->htmlTag(
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
    'NumNonnotified' => $renderNumPackages,
    'NumSuccess' => $renderNumPackages,
    'NumNotified' => $renderNumPackages,
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
        'NumNonnotified' => 'textright',
        'NumSuccess' => 'textright',
        'NumNotified' => 'textright',
        'NumError' => 'textright',
    ),
    $rowClassCallback
);
