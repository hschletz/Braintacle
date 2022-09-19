<?php

/**
 * Display all subnets
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

print $this->htmlElement(
    'h2',
    $this->translate('Identified network devices')
);

$headers = array(
    'Description' => $this->translate('Type'),
    'Count' => $this->translate('Count'),
);

$renderCallbacks = array(
    'Count' => function ($view, $deviceType, $property) {
        $count = $deviceType['Count'];
        if ($count) {
            return $view->htmlElement(
                'a',
                $count,
                array(
                    'href' => $view->consoleUrl(
                        'network',
                        'showidentified',
                        array(
                            'type' => $deviceType['Description'],
                        )
                    ),
                )
            );
        } else {
            return '0';
        }
    },
);

print $this->table(
    $this->devices,
    $headers,
    array(),
    $renderCallbacks
);
print $this->htmlElement(
    'p',
    $this->htmlElement(
        'a',
        $this->translate('Manage device types'),
        array('href' => $this->consoleUrl('preferences', 'networkdevices')),
        true
    ),
    array('class' => 'textcenter')
);

print $this->htmlElement(
    'h2',
    $this->translate('Subnets')
);

$headers = array(
    'Name' => $this->translate('Name'),
    'CidrAddress' => $this->translate('Address'),
    'NumInventoried' => $this->translate('inventoried'),
    'NumIdentified' => $this->translate('identified'),
    'NumUnknown' => $this->translate('unknown'),
);

$renderUninventoried = function ($view, $subnet, $property) {
    $count = $subnet[$property];
    if ($count) {
        return $view->htmlElement(
            'a',
            $count,
            array(
                'href' => $view->consoleUrl(
                    'network',
                    $property == 'NumIdentified' ? 'showidentified' : 'showunknown',
                    array(
                        'subnet' => $subnet['Address'],
                        'mask' => $subnet['Mask']
                    )
                ),
            )
        );
    } else {
        return '0';
    }
};
$renderCallbacks = array(
    'Name' => function ($view, $subnet, $property) {
        // Link to edit subnet properties. If no name is defined, use gray
        // 'Edit' as link text.
        $name = $subnet['Name'];
        $attributes = array(
            'href' => $view->consoleUrl(
                'network',
                'properties',
                array(
                    'subnet' => $subnet['Address'],
                    'mask' => $subnet['Mask']
                )
            ),
        );
        if ($name) {
            $name = $view->escapeHtml($name);
        } else {
            $name = $view->translate('Edit');
            $attributes['class'] = 'blur';
        }
        return $view->htmlElement(
            'a',
            $name,
            $attributes,
            true
        );
    },
    'NumInventoried' => function ($view, $subnet, $property) {
        // The number is always >= 1. There is no need to check for 0.
        return $view->htmlElement(
            'a',
            $view->escapeHtml($subnet['NumInventoried']),
            array(
                'href' => $view->consoleUrl(
                    'client',
                    'index',
                    array(
                        'filter1' => 'NetworkInterface.Subnet',
                        'exact1' => '1',
                        'search1' => $subnet['Address'],
                        'filter2' => 'NetworkInterface.Netmask',
                        'exact2' => '1',
                        'search2' => $subnet['Mask'],
                        'columns' => 'Name,UserName,Type,InventoryDate',
                        'jumpto' => 'network',
                        'distinct' => null,
                    )
                ),
            ),
            true
        );
    },
    'NumIdentified' => $renderUninventoried,
    'NumUnknown' => $renderUninventoried,
);

print $this->table(
    $this->subnets,
    $headers,
    $this->subnetOrder,
    $renderCallbacks
);
