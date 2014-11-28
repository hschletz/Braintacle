<?php
/**
 * Form for display/setting of 'system' preferences
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
 *
 * @package Forms
 */
/**
 * Form for display/setting of 'system' preferences
 * @package Forms
 */
class Form_Preferences_System extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'CommunicationServerUri' => 'text',
        'LockValidity' => 'integer',
        'SessionValidity' => 'integer',
        'SessionCleanupInterval' => 'integer',
        'SessionRequired' => 'bool',
        'TraceDeleted' => 'bool',
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

    /** {@inheritdoc} */
    protected $_goodValues = array(
        'AutoDuplicateCriteria' => 0,
        'UseTransactions' => true,
        'UseCacheTables' => false,
        'AcceptNonZlib' => true,
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'CommunicationServerUri' => $translate->_(
                'Communication server URI'
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
        $this->getElement('CommunicationServerUri')
            ->addValidator(new Zend_Validate_Callback(array('Zend_Uri', 'check')));
        $this->getElement('LockValidity')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('SessionValidity')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('SessionCleanupInterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('AutoDuplicateCriteria')
            ->addValidator('GreaterThan', false, array('min' => -1))
            ->setAttrib('size', '5');
        $this->getElement('CacheTableExpirationinterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
    }

}
