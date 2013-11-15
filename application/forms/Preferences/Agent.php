<?php
/**
 * Form for display/setting of 'agent' preferences
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
 * Form for display/setting of 'agent' preferences
 * @package Forms
 */
class Form_Preferences_Agent extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'contactInterval' => 'integer',
        'inventoryInterval' => 'integer',
        'agentWhitelistFile' => 'text',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        $this->_labels = array(
            'contactInterval' => $translate->_(
                'Agent contact interval (in hours)'
            ),
            'inventoryInterval' => $translate->_(
                'Inventory interval (in days, 0 = always, -1 = never)'
            ),
            'agentWhitelistFile' => $translate->_(
                'File with allowed non-OCS agents (FusionInventory etc.)'
            ),
        );
        parent::init();
        $this->getElement('contactInterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('inventoryInterval')
            ->addValidator('GreaterThan', false, array('min' => -2))
            ->setAttrib('size', '5');
        $this->getElement('agentWhitelistFile')
            ->addFilter('StringTrim')
            ->addValidator(new Braintacle_Validate_FileReadable);
    }

}
