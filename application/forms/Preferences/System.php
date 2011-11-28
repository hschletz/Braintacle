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
 * Includes
 */
require_once ('Braintacle/Validate/DirectoryWritable.php');
/**
 * Form for display/setting of 'system' preferences
 * @package Forms
 */
class Form_Preferences_System extends Form_Preferences
{

    protected $_types = array(
        'CommunicationServerAddress' => 'text',
        'CommunicationServerPort' => 'text', // Not integer, because normalization would be inadequate
        'LockValidity' => 'integer',
        'SessionValidity' => 'integer',
        'SessionCleanupInterval' => 'integer',
        'SessionRequired' => 'bool',
        'TraceDeleted' => 'bool',
        'LogPath' => 'text',
        'LogLevel' => array(0, 1, 2),
        'AutoDuplicateCriteria' => 'integer',
        'UpdateChangedSectionsOnly' => 'bool',
        'UpdateChangedSnmpSectionsOnly' => 'bool',
        'UseDifferentialUpdate' => 'bool',
        'UseTransactions' => 'bool',
        'UseCacheTables' => 'bool',
        'KeepObsoleteCacheItems' => 'bool',
        'CacheTableExpirationinterval' => 'integer',
        'AcceptNonZlib' => 'bool',
    );

    protected $_goodValues = array(
        'AutoDuplicateCriteria' => 0,
        'UseTransactions' => true,
        'UseCacheTables' => false,
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
            'LockValidity' => $translate->_(
                'Maximum seconds to lock a computer'
            ),
            'SessionValidity' => $translate->_(
                'Maximum duration of an agent session in seconds'
            ),
            'SessionCleanupInterval' => $translate->_(
                'Interval in seconds to cleanup sessions'
            ),
            'SessionRequired' => $translate->_(
                'Session required for inventory'
            ),
            'TraceDeleted' => $translate->_(
                'Keep track of deleted computers'
            ),
            'LogPath' => $translate->_(
                'Path to logfiles'
            ),
            'LogLevel' => $translate->_(
                'Log level'
            ),
            'AutoDuplicateCriteria' => $translate->_(
                'Bitmask for automatic resolution of duplicates (should be 0)'
            ),
            'UpdateChangedSectionsOnly' => $translate->_(
                'Update only changed inventory sections'
            ),
            'UpdateChangedSnmpSectionsOnly' => $translate->_(
                'Update only changed SNMP sections'
            ),
            'UseDifferentialUpdate' => $translate->_(
                'Use differential database updates'
            ),
            'UseTransactions' => $translate->_(
                'Use database transactions (recommended)'
            ),
            'UseCacheTables' => $translate->_(
                'Use cache tables (not recommended)'
            ),
            'KeepObsoleteCacheItems' => $translate->_(
                'Keep obsolete items in the cache'
            ),
            'CacheTableExpirationinterval' => $translate->_(
                'Days between cache rebuilds'
            ),
            'AcceptNonZlib' => $translate->_(
                'Accept requests other than raw zlib compressed'
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
        $this->getElement('LockValidity')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('SessionValidity')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('SessionCleanupInterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('LogPath')
            ->addValidator(new Braintacle_Validate_DirectoryWritable);
        $this->getElement('AutoDuplicateCriteria')
            ->addValidator('GreaterThan', false, array('min' => -1))
            ->setAttrib('size', '5');
        $this->getElement('CacheTableExpirationinterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
    }

}
