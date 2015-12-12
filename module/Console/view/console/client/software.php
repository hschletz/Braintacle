<?php
/**
 * Display installled software
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
    'Name' => $this->translate('Name'),
    'Version' => $this->translate('Version'),
);
if ($client['Windows'] instanceof \Model\Client\WindowsInstallation) {
    $headers['Publisher'] = $this->translate('Publisher');
    $headers['InstallLocation'] = $this->translate('Location');
    $headers['Architecture'] = $this->translate('Architecture');
} else {
    $headers['Size'] = $this->translate('Size');
}

$columnClasses = array(
    'Size' => 'textright',
);

$renderCallbacks = array(
    'Name' => function ($view, $software) {
        $content = $view->escapeHtml($software['Name']);
        if ($software['Comment']) {
            $content = $view->htmlTag(
                'span',
                $content,
                array('title' => $software['Comment']),
                true
            );
        }
        if ($software['NumInstances'] > 1) {
            $content .= ' ';
            $content .= $view->htmlTag(
                'span',
                sprintf('(%d)', $software['NumInstances']),
                array(
                    'class' => 'duplicate',
                ),
                true
            );
        }
        return $content;
    },
    'Size' => function ($view, $software) {
        $size = $view->numberFormat(
            $software['Size'],
            \NumberFormatter::DECIMAL,
            \NumberFormatter::TYPE_DOUBLE,
            null,
            0
        );
        $size .= "\xC2\xA0kB";
        return $view->escapeHtml($size);
    },
    'Architecture' => function ($view, $software) {
        $architecture = $software['Architecture'];
        if ($architecture) {
            $architecture = $view->escapeHtml($architecture . ' Bit');
        }
        return $architecture;
    },
);

$filters = array();
if (!$this->displayBlacklistedSoftware) {
    $filters['Software.NotIgnored'] = null;
}

// Compact list by suppressing duplicate entries, adding the number of instances for each entry.
$list = array();
foreach ($client->getItems('Software', $this->order, $this->direction, $filters) as $software) {
    $key = json_encode($software);
    if (isset($list[$key])) {
        $list[$key]['NumInstances']++;
    } else {
        $software['NumInstances'] = 1;
        $list[$key] = $software;
    }
}

print $this->table(
    $list,
    $headers,
    array('order' => $this->order, 'direction' => $this->direction),
    $renderCallbacks,
    $columnClasses
);
