<?php

/**
 * Display list of clients
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Http\RouteHelper;

/** @var RouteHelper */
$routeHelper = $this->routeHelper;

if ($this->successMessages) {
    print $this->htmlList(
        $this->successMessages,
        false,
        ['class' => 'success'],
        false
    );
}

// Column headers
$allHeaders = array(
    'Id' => $this->translate('ID'),
    'IdString' => $this->translate('ID string'),
    'Name' => $this->translate('Name'),
    'CpuClock' => $this->translate('CPU clock'),
    'CpuCores' => $this->translate('CPU cores'),
    'CpuType' => $this->translate('CPU type'),
    'InventoryDate' => $this->translate('Last inventory'),
    'LastContactDate' => $this->translate('Last contact'),
    'PhysicalMemory' => $this->translate('Memory'),
    'SwapMemory' => $this->translate('Swap'),
    'IpAddress' => $this->translate('IP address'),
    'DnsServer' => $this->translate('DNS server'),
    'DefaultGateway' => $this->translate('Default gateway'),
    'OsName' => $this->translate('Operating system'),
    'OsVersionNumber' => $this->translate('OS version number'),
    'OsVersionString' => $this->translate('OS version string'),
    'OsComment' => $this->translate('OS comment'),
    'UserName' => $this->translate('User'),
    'Manufacturer' => $this->translate('Manufacturer'),
    'ProductName' => $this->translate('Model'),
    'Serial' => $this->translate('Serial number'),
    'Type' => $this->translate('Type'),
    'UserAgent' => $this->translate('User agent'),
    'Uuid' => $this->translate('UUID'),
    'BiosManufacturer' => $this->translate('BIOS manufacturer'),
    'BiosVersion' => $this->translate('BIOS version'),
    'BiosDate' => $this->translate('BIOS date'),
    'AssetTag' => $this->translate('Asset tag'),
    'Package.Status' => $this->translate('Error code'),
    'AudioDevice.Name' => $this->translate('Audio device'),
    'Controller.Name' => $this->translate('Controller'),
    'Display.Manufacturer' => $this->translate('Monitor: manufacturer'),
    'Display.Description' => $this->translate('Monitor: description'),
    'Display.Serial' => $this->translate('Monitor: serial'),
    'Display.Edid' => $this->translate('Monitor: EDID'),
    'DisplayController.Name' => $this->translate('Display controller'),
    'DisplayController.Memory' => $this->translate('GPU memory'),
    'ExtensionSlot.Name' => $this->translate('Extension slot'),
    'Filesystem.Size' => $this->translate('Filesystem size (MB)'),
    'Filesystem.FreeSpace' => $this->translate('Free space (MB)'),
    'Modem.Name' => $this->translate('Modem'),
    'NetworkInterface.MacAddress' => $this->translate('MAC address'),
    'NetworkInterface.IpAddress' => $this->translate('IP address'),
    'NetworkInterface.Subnet' => $this->translate('Network address'),
    'NetworkInterface.Netmask' => $this->translate('Netmask'),
    'Port.Name' => $this->translate('Port name'),
    'Printer.Name' => $this->translate('Printer name'),
    'Printer.Port' => $this->translate('Printer port'),
    'Printer.Driver' => $this->translate('Printer driver'),
    'Software.name' => $this->translate('Software: Name'),
    'Software.version' => $this->translate('Software: Version'),
    'Software.publisher' => $this->translate('Software: Publisher'),
    'Software.comment' => $this->translate('Software: Comment'),
    'Software.installLocation' => $this->translate('Software: Install location'),
    'Windows.UserDomain' => $this->translate('User domain'),
    'Windows.Company' => $this->translate('Windows company'),
    'Windows.Owner' => $this->translate('Windows owner'),
    'Windows.ProductKey' => $this->translate('Windows product key'),
    'Windows.ManualProductKey' => $this->translate('Windows product key (manual)'),
    'Windows.ProductId' => $this->translate('Windows product ID'),
    'Windows.Workgroup' => $this->translate('Workgroup'),
    'Windows.CpuArchitecture' => $this->translate('OS architecture'),
    'MsOfficeProduct.ProductKey' => $this->translate('MS Office product key'),
    'MsOfficeProduct.ProductId' => $this->translate('MS Office product ID'),
);

$columnClasses = array(
    'Id' => 'textright',
    'CpuClock' => 'textright',
    'CpuCores' => 'textright',
    'PhysicalMemory' => 'textright',
    'SwapMemory' => 'textright',
    'DisplayController.Memory' => 'textright',
    'Filesystem.Size' => 'textright',
    'Filesystem.FreeSpace' => 'textright',
);

$headers = array();
$renderCallbacks = array(
    'Name' => function ($view, $client) use ($routeHelper) {
        return $view->htmlElement(
            'a',
            $view->escapeHtml($client['Name']),
            ['href' => $routeHelper->getPathForRoute('showClientGeneral', ['id' => $client['Id']])],
            true
        );
    },
    'OsName' => function ($view, $client) {
        // Strip prefix to conserve space
        return $view->escapeHtml(preg_replace('/^Microsoft\x{00AE}? /u', '', $client['OsName']));
    },
);

foreach ($this->columns as $column) {
    $column = ucfirst($column);
    $headers[$column] = $allHeaders[$column];
}

$filter = $this->filter;
$count = count($this->clients);
if ($filter) {
    $header = $this->filterDescription($filter, $this->search, $count);
} else {
    $header = sprintf(
        $this->translate('Number of clients: %d'),
        $count
    );
}

print $this->htmlElement(
    'p',
    $header,
    array('class' => 'textcenter')
);

print $this->table(
    $this->clients,
    $headers,
    array('order' => $this->order, 'direction' => $this->direction),
    $renderCallbacks,
    $columnClasses
);
