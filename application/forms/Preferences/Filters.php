<?php
/**
 * Form for display/setting of 'filters' preferences
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * Form for display/setting of 'filters' preferences
 * @package Forms
 */
class Form_Preferences_Filters extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'trustedNetworksOnly' => 'bool',
        'inventoryFilter' => 'bool',
        'limitInventory' => 'bool',
        'limitInventoryInterval' => 'integer',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'trustedNetworksOnly' => $translate->_(
                'Limit agent connections to trusted networks'
            ),
            'inventoryFilter' => $translate->_(
                'Limit inventory frequency'
            ),
            'limitInventory' => $translate->_(
                'Limit inventory processing per IP address'
            ),
            'limitInventoryInterval' => $translate->_(
                'Seconds between inventory processing'
            ),
        );
        parent::init();
        $this->getElement('limitInventoryInterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', 5);
    }

}
