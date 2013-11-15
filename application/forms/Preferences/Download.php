<?php
/**
 * Form for display/setting of 'download' preferences
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
 * Form for display/setting of 'download' preferences
 * @package Forms
 */
class Form_Preferences_Download extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'packageDeployment' => 'bool',
        'downloadPeriodDelay' => 'integer',
        'downloadCycleDelay' => 'integer',
        'downloadFragmentDelay' => 'integer',
        'downloadMaxPriority' => 'integer',
        'downloadTimeout' => 'integer',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'packageDeployment' => $translate->_(
                'Enable package download'
            ),
            'downloadPeriodDelay' => $translate->_(
                'Delay (in seconds) between periods'
            ),
            'downloadCycleDelay' => $translate->_(
                'Delay (in seconds) between cycles'
            ),
            'downloadFragmentDelay' => $translate->_(
                'Delay (in seconds) between fragments'
            ),
            'downloadMaxPriority' => $translate->_(
                'Maximum package priority (packages with higher value will not be downloaded)'
            ),
            'downloadTimeout' => $translate->_(
                'Timeout (in days)'
            ),
        );
        parent::init();
        $this->getElement('downloadPeriodDelay')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('downloadCycleDelay')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('downloadFragmentDelay')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('downloadMaxPriority')
            ->addValidator('Between', false, array('min' => 0, 'max' => 10))
            ->setAttrib('size', '5');
        $this->getElement('downloadTimeout')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
    }

}
