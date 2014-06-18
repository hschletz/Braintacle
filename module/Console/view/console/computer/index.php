<?php
/**
 * Display list of computers
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
            $this->formatMessages($messages),
            false,
            array('class' => $namespace),
            false
        );
    }
}

// Column headers
$allHeaders = array(
    'Id' => $this->translate('ID'),
    'ClientId' => $this->translate('Client ID'),
    'Name' => $this->translate('Name'),
    'Workgroup' => $this->translate('Workgroup'),
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
    'OcsAgent' => $this->translate('OCS agent'),
    'OsName' => $this->translate('Operating system'),
    'OsVersionNumber' => $this->translate('OS version number'),
    'OsVersionString' => $this->translate('OS version string'),
    'OsComment' => $this->translate('OS comment'),
    'UserName' => $this->translate('User'),
    'Manufacturer' => $this->translate('Manufacturer'),
    'Model' => $this->translate('Model'),
    'Serial' => $this->translate('Serial number'),
    'Type' => $this->translate('Type'),
    'Uuid' => $this->translate('UUID'),
    'BiosManufacturer' => $this->translate('BIOS manufacturer'),
    'BiosVersion' => $this->translate('BIOS version'),
    'BiosDate' => $this->translate('BIOS date'),
    'AssetTag' => $this->translate('Asset tag'),
    'Package.Status' => $this->translate('Error code'),
    'Software.Version' => $this->translate('Version'),
    'AudioDevice.Name' => $this->translate('Audio device'),
    'Controller.Name' => $this->translate('Controller'),
    'Display.Manufacturer' => $this->translate('Monitor: manufacturer'),
    'Display.Description' => $this->translate('Monitor: description'),
    'Display.Serial' => $this->translate('Monitor: serial'),
    'Display.ProductionDate' => $this->translate('Monitor: production date'),
    'DisplayController.Name' => $this->translate('Display controller'),
    'DisplayController.Memory' => $this->translate('GPU memory'),
    'ExtensionSlot.Name' => $this->translate('Extension slot'),
    'Modem.Name' => $this->translate('Modem'),
    'NetworkInterface.MacAddress' => $this->translate('MAC address'),
    'NetworkInterface.IpAddress' => $this->translate('IP address'),
    'NetworkInterface.Subnet' => $this->translate('Network address'),
    'NetworkInterface.Netmask' => $this->translate('Netmask'),
    'Port.Name' => $this->translate('Port name'),
    'Printer.Name' => $this->translate('Printer name'),
    'Printer.Port' => $this->translate('Printer port'),
    'Printer.Driver' => $this->translate('Printer driver'),
    'Software.Name' => $this->translate('Software: Name'),
    'Software.Version' => $this->translate('Software: Version'),
    'Software.Publisher' => $this->translate('Software: Publisher'),
    'Software.Comment' => $this->translate('Software: Comment'),
    'Software.InstallLocation' => $this->translate('Software: Install location'),
    'Volume.Size' => $this->translate('Volume size'),
    'Volume.FreeSpace' => $this->translate('Free space'),
    'Windows.UserDomain' => $this->translate('User domain'),
    'Windows.Company' => $this->translate('Windows company'),
    'Windows.Owner' => $this->translate('Windows owner'),
    'Windows.ProductKey' => $this->translate('Windows product key'),
    'Windows.ManualProductKey' => $this->translate('Windows product key (manual)'),
    'Windows.ProductId' => $this->translate('Windows product ID'),
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
    'Volume.Size' => 'textright',
    'Volume.FreeSpace' => 'textright',
);

$headers = array();
foreach ($this->columns as $column) {
    if (preg_match('/^(Registry|UserDefinedInfo)\.(.+)/', $column, $matches)) {
        // Extract column header from name
        if ($matches[1] == 'UserDefinedInfo' and $matches[2] == 'TAG') {
            $headers[$column] = $this->translate('Category');
        } else {
            $headers[$column] = $matches[2];
        }
    } else {
        $headers[$column] = $allHeaders[$column];
    }
}

$renderCallbacks = array(
    'Name' => function($view, $computer) {
        return $view->htmlTag(
            'a',
            $view->escapeHtml($computer['Name']),
            array(
                'href' => $view->consoleUrl(
                    'computer',
                    $view->jumpto,
                    array('id' => $computer['Id'])
                )
            ),
            true
        );
    },
);

$filter = $this->filter;
$count = count($this->computers);
if ($filter) {
    $search = $this->search;
    if ($this->isCustomSearch) {
        // Display the number of results and links to edit the filter or add it
        // to a group.
        if ($search instanceof \Zend_Date) {
            $search = $search->get('yyyy-MM-dd');
        }
        $params = array(
            'filter' => $filter,
            'search' => $search,
            'operator' => $this->operator,
            'invert' => $this->invert,
        );
        $header = sprintf($this->translate('%d matches'), $count)
                . "<br>\n"
                . $this->htmlTag(
                    'a',
                    $this->translate('Edit filter'),
                    array('href' => $this->consoleUrl('computer', 'search', $params)),
                    true
                )
                . "\n&nbsp;&nbsp;&nbsp;\n"
                . $this->htmlTag(
                    'a',
                    $this->translate('Save to group'),
                    array('href' => $this->consoleUrl('group', 'add', $params)),
                    true
                );
    } else {
        // For fixed filters, print a nicer description.
        if ($filter == 'Software') {
            $search = \Zend\Filter\StaticFilter::execute($search, 'Library\FixEncodingErrors');
        }
        $header = \Model_Computer::getFilterDescription($filter, $search, $count);
    }
} else {
    $header = sprintf(
        $this->translate('Number of computers: %d'),
        $count
    );
}

print $this->htmlTag(
    'p',
    $header,
    array('class' => 'textcenter')
);

print $this->table(
    $this->computers,
    $headers,
    array('order' => $this->order, 'direction' => $this->direction),
    $renderCallbacks,
    $columnClasses
);
