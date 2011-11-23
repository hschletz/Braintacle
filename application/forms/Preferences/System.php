<?php
/**
 * Form for display/setting of 'system' preferences
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
 * Form for display/setting of 'system' preferences
 * @package Forms
 */
class Form_Preferences_System extends Form_Preferences
{

    protected $_types = array(
        'CommunicationServerAddress' => 'text',
        'CommunicationServerPort' => 'text', // Not integer, because normalization would be inadequate
        'GroupCacheExpirationInterval' => 'integer',
        'GroupCacheExpirationFuzz' => 'integer',
        'LockValidity' => 'integer',
        'TraceDeleted' => 'bool',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'CommunicationServerAddress' => $translate->_(
                'Communication server hostname/address'
            ),
            'CommunicationServerPort' => $translate->_(
                'Communication server port'
            ),
            'GroupCacheExpirationInterval' => $translate->_(
                'Minimum seconds between group cache rebuilds'
            ),
            'GroupCacheExpirationFuzz' => $translate->_(
                'Maximum seconds added to above value'
            ),
            'LockValidity' => $translate->_(
                'Maximum seconds to lock a computer'
            ),
            'TraceDeleted' => $translate->_(
                'Keep track of deleted computers'
            ),
        );
        parent::init();
        $this->getElement('CommunicationServerAddress')
            ->addValidator(
                'Hostname',
                false,
                array(
                    'allow' => Zend_Validate_Hostname::ALLOW_DNS |
                               Zend_Validate_Hostname::ALLOW_IP |
                               Zend_Validate_Hostname::ALLOW_LOCAL,
                    'tld' => false,
                )
            );
        $this->getElement('CommunicationServerPort')
            ->setAttrib('size', '5')
            ->addValidator('Digits')
            ->addValidator(
                'Between',
                false,
                array(
                    'min' => 1,
                    'max' => 65535,
                )
            );
        $this->getElement('GroupCacheExpirationInterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('GroupCacheExpirationFuzz')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('LockValidity')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
    }

}
