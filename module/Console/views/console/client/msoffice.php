<?php

/**
 * Display MS Office products
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
 */

print $this->clientHeader($this->client);

$client = $this->client;

$headers = array(
    'Name' => $this->translate('Product'),
    'Architecture' => $this->translate('Architecture'),
    'ProductKey' => $this->translate('Product key'),
    'ProductId' => $this->translate('Product ID'),
);

$renderCallbacks = array(
    'Name' => function ($view, $product) {
        $name = $view->escapeHtml($product['Name']);
        $description = $product['ExtraDescription'];
        if ($description) {
            $name .= ' (' . $view->escapeHtml($description) . ')';
        }
        $guid = $product['Guid'];
        if ($guid) {
            return $view->htmlElement(
                'span',
                $name,
                array('title' => "GUID: $guid"),
                true
            );
        } else {
            return $name;
        }
    },
    'Architecture' => function ($view, $product) {
        return $view->escapeHtml($product['Architecture'] . ' Bit');
    },
);

$installedProducts = $client->getItems(
    'MsOfficeProduct',
    $this->order,
    $this->direction,
    array('Type' => \Model\Client\Item\MsOfficeProduct::TYPE_INSTALLED_PRODUCT)
);
if (count($installedProducts)) {
    print $this->htmlElement(
        'h2',
        $this->translate('Installed Microsoft Office products')
    );
    print $this->table(
        $installedProducts,
        $headers,
        array('order' => $this->order, 'direction' => $this->direction),
        $renderCallbacks
    );
}

$unusedLicenses = $client->getItems(
    'MsOfficeProduct',
    $this->order,
    $this->direction,
    array('Type' => \Model\Client\Item\MsOfficeProduct::TYPE_UNUSED_LICENSE)
);
if (count($unusedLicenses)) {
    print $this->htmlElement(
        'h2',
        $this->translate('Unused Microsoft Office licenses')
    );
    print $this->table(
        $unusedLicenses,
        $headers,
        array('order' => $this->order, 'direction' => $this->direction),
        $renderCallbacks
    );
}
