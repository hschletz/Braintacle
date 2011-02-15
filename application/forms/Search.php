<?php
/**
 * Search form.
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @package Forms
 * @filesource
 */
/**
 * search form
 * @package Forms
 */
class Form_Search extends Zend_Form
{

    /**
     * Build form elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        $this->setMethod('post');

        $filter = new Zend_Form_Element_Select('filter');
        $filter->setLabel('Search for')
               ->setDisableTranslator(true) // Translate manually to make xgettext find the strings
               ->setMultiOptions(
                   array(
                       'Name' => $translate->_('Computer name'),
                       'UserName' => $translate->_('User name'),
                       'UserDomain' => $translate->_('User domain'),
                       'Workgroup' => $translate->_('Workgroup'),
                       'OsName' => $translate->_('OS name'),
                       'OsVersionNumber' => $translate->_('OS version number'),
                       'OsVersionString' => $translate->_('OS version string'),
                       'OsComment' => $translate->_('OS comment'),
                       'WindowsProductkey' => $translate->_('Product key'),
                       'Software.Name' => $translate->_('Software: Name'),
                       'Software.Version' => $translate->_('Software: Version'),
                       'Software.Publisher' => $translate->_('Software: Publisher'),
                       'Software.Comment' => $translate->_('Software: Comment'),
                       'Software.InstallLocation' => $translate->_('Software: Install location'),
                       'CpuType' => $translate->_('CPU type'),
                       'Manufacturer' => $translate->_('Manufacturer'),
                       'Model' => $translate->_('Model'),
                       'Serial' => $translate->_('Serial number'),
                       'AssetTag' => $translate->_('Asset tag'),
                       'BiosVersion' => $translate->_('BIOS version'),
                       'BiosDate' => $translate->_('BIOS date'),
                       'DnsServer' => $translate->_('DNS server'),
                       'DefaultGateway' => $translate->_('Default gateway'),
                       'NetworkInterface.IpAddress' => $translate->_('IP address'),
                       'NetworkInterface.MacAddress' => $translate->_('MAC address'),
                       'NetworkInterface.Subnet' => $translate->_('Network address'),
                       'NetworkInterface.Netmask' => $translate->_('Network Mask'),
                       'Printer.Name' => $translate->_('Printer name'),
                       'Printer.Port' => $translate->_('Printer port'),
                       'Printer.Driver' => $translate->_('Printer driver'),
                       'OcsAgent' => $translate->_('OCS agent'),
                       'Display.Manufacturer' => $translate->_('Monitor: manufacturer'),
                       'Display.Description' => $translate->_('Monitor: description'),
                       'Display.Serial' => $translate->_('Monitor: serial'),
                       'Display.ProductionDate' => $translate->_('Monitor: production date'),
                       'DisplayController.Name' => $translate->_('Display controller'),
                       'Modem.Name' => $translate->_('Modem'),
                       'AudioDevice.Name' => $translate->_('Audio device'),
                       'Controller.Name' => $translate->_('Controller'),
                       'Port.Name' => $translate->_('Port name'),
                       'ExtensionSlot.Name' => $translate->_('Extension slot'),
                   )
               );
        $this->addElement($filter);

        $search = new Zend_Form_Element_Text('search');
        $search->setLabel('Value')
               ->addValidator('StringLength', false, array(0, 255));
        $this->addElement($search);

        $exact = new Zend_Form_Element_Checkbox('exact');
        $exact->setLabel('Exact match');
        $this->addElement($exact);

        $invert = new Zend_Form_Element_Checkbox('invert');
        $invert->setLabel('Invert results');
        $this->addElement($invert);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Search')
               ->setIgnore(true);
        $this->addElement($submit);
    }

}
