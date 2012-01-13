<?php
/**
 * Form for display/setting of 'inventory' preferences
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 * Form for display/setting of 'inventory' preferences
 * @package Forms
 */
class Form_Preferences_Inventory extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'InspectRegistry' => 'bool',
        'DefaultMergeUserdefined' => 'bool',
        'DefaultMergeGroups' => 'bool',
        'DefaultMergePackages' => 'bool',
        'DefaultDeleteInterfaces' => 'bool',
    );

    /**
     * Translate labels before calling parent implementation
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'InspectRegistry' => $translate->_(
                'Inspect registry'
            ),
            'DefaultMergeUserdefined' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Merge user supplied information')
            ),
            'DefaultMergeGroups' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Merge manual group assignments')
            ),
            'DefaultMergePackages' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Merge missing package assignments')
            ),
            'DefaultDeleteInterfaces' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Delete interfaces from network listing')
            ),
        );
        parent::init();
    }

}
