<?php

/**
 * Display list of clients
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

foreach (array('error', 'success') as $namespace) {
    $messages = $this->flashMessenger()->getMessagesFromNamespace($namespace);
    if ($messages) {
        print $this->htmlList(
            $messages,
            false,
            array('class' => $namespace),
            false
        );
    }
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
    'Name' => function ($view, $client) {
        return $view->htmlElement(
            'a',
            $view->escapeHtml($client['Name']),
            array(
                'href' => $view->consoleUrl(
                    'client',
                    $view->jumpto,
                    array('id' => $client['Id'])
                )
            ),
            true
        );
    },
    'OsName' => function ($view, $client) {
        // Strip prefix to conserve space
        return $view->escapeHtml(preg_replace('/^Microsoft\x{00AE}? /u', '', $client['OsName']));
    },
);

foreach ($this->columns as $column) {
    if (preg_match('/^(Registry|CustomFields)\.(.+)/', $column, $matches)) {
        // Extract column header from name
        $headers[$column] = $matches[2];
        if ($matches[1] == 'CustomFields') {
            if ($matches[2] == 'TAG') {
                $headers[$column] = $this->translate('Category');
            } else {
                $renderCallbacks[$column] = function ($view, $client, $property) {
                    $value = $client[$property];
                    if ($value instanceof \DateTime) {
                        $value = $this->dateFormat($value, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE);
                    }
                    return $view->escapeHtml($value);
                };
            }
        }
    } else {
        $headers[$column] = $allHeaders[$column];
    }
}

$filter = $this->filter;
$count = count($this->clients);
if ($filter) {
    $search = $this->search;
    if ($this->isCustomSearch) {
        // Display the number of results and links to edit the filter or add it
        // to a group.
        if ($search instanceof \DateTime) {
            $search = $search->format('Y-m-d');
        }
        $params = array(
            'filter' => $filter,
            'search' => $search,
            'operator' => $this->operator,
            'invert' => $this->invert,
        );
        $header = sprintf($this->translate('%d matches'), $count)
                . "<br>\n"
                . $this->htmlElement(
                    'a',
                    $this->translate('Edit filter'),
                    array('href' => $this->consoleUrl('client', 'search', $params)),
                    true
                )
                . "\n&nbsp;&nbsp;&nbsp;\n"
                . $this->htmlElement(
                    'a',
                    $this->translate('Save to group'),
                    array('href' => $this->consoleUrl('group', 'add', $params)),
                    true
                );
    } else {
        // For fixed filters, print a nicer description.
        if ($filter == 'Software') {
            $search = \Laminas\Filter\StaticFilter::execute($search, 'Library\FixEncodingErrors');
        }
        $header = $this->filterDescription($filter, $search, $count);
    }
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
