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
        'communicationServerUri' => 'text',
        'lockValidity' => 'integer',
        'sessionValidity' => 'integer',
        'sessionCleanupInterval' => 'integer',
        'sessionRequired' => 'bool',
        'logPath' => 'text',
        'logLevel' => array(0, 1, 2),
        'autoDuplicateCriteria' => 'integer',
    );

    /** {@inheritdoc} */
    protected $_goodValues = array(
        'autoDuplicateCriteria' => 0,
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'communicationServerUri' => $translate->_(
                'Communication server URI'
            ),
            'lockValidity' => $translate->_(
                'Maximum seconds to lock a computer'
            ),
            'sessionValidity' => $translate->_(
                'Maximum duration of an agent session in seconds'
            ),
            'sessionCleanupInterval' => $translate->_(
                'Interval in seconds to cleanup sessions'
            ),
            'sessionRequired' => $translate->_(
                'Session required for inventory'
            ),
            'logPath' => $translate->_(
                'Path to logfiles'
            ),
            'logLevel' => $translate->_(
                'Log level'
            ),
            'autoDuplicateCriteria' => $translate->_(
                'Bitmask for automatic resolution of duplicates (should be 0)'
            ),
        );
        parent::init();
        $this->getElement('communicationServerUri')
            ->addValidator(new Zend_Validate_Callback(array($this, 'validateUri')));
        $this->getElement('lockValidity')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('sessionValidity')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('sessionCleanupInterval')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('logPath')
            ->addValidator(new Braintacle_Validate_DirectoryWritable);
        $this->getElement('autoDuplicateCriteria')
            ->addValidator('GreaterThan', false, array('min' => -1))
            ->setAttrib('size', '5');
    }

    /**
     * URI validation callback
     * @internal
     * @param string $uri
     * @return bool
     */
    public function validateUri($uri)
    {
        try {
            \Zend\Uri\UriFactory::factory($uri);
            return true;
        } catch(\Zend\Uri\Exception\InvalidArgumentException $e) {
            return false;
        } catch(\Exception $e) {
            throw $e;
        }
    }
}
