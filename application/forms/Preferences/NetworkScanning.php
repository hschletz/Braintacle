<?php
/**
 * Form for display/setting of 'network scanning' preferences
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
 * @package Forms
 */
/**
 * Form for display/setting of 'network scanning' preferences
 * @package Forms
 */
class Form_Preferences_NetworkScanning extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'ScannersPerSubnet' => 'integer',
        'ScanSnmp' => 'bool',
        'ScannerMinDays' => 'integer',
        'ScannerMaxDays' => 'integer',
        'ScanAlways' => 'bool',
        'ScanningConfigurationInGroups' => 'bool',
        'ScanArpDelay' => 'integer',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'ScannersPerSubnet' => $translate->_(
                'Number of scanning computers per subnet'
            ),
            'ScanSnmp' => $translate->_(
                'Use SNMP'
            ),
            'ScannerMinDays' => $translate->_(
                'Minimum days before a scanning computer is replaced'
            ),
            'ScannerMaxDays' => $translate->_(
                'Maximum days before a scanning computer is replaced'
            ),
            'ScanAlways' => $translate->_(
                'Always scan, even if no computer meets quality criteria'
            ),
            'ScanningConfigurationInGroups' => $translate->_(
                'Use configuration in groups'
            ),
            'ScanArpDelay' => $translate->_(
                'Delay (in milliseconds) between ARP requests'
            ),
            'ScanArpDelay' => $translate->_(
                'Delay (in milliseconds) between ARP requests'
            ),
        );
        parent::init();
        $this->getElement('ScannersPerSubnet')
            ->addValidator('GreaterThan', false, array('min' => -1))
            ->setAttrib('size', '5');
        $this->getElement('ScannerMinDays')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('ScannerMaxDays')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('ScanArpDelay')
            ->addValidator('GreaterThan', false, array('min' => 9))
            ->setAttrib('size', '5');
    }

}
