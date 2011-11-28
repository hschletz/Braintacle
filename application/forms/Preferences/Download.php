<?php
/**
 * Form for display/setting of 'download' preferences
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
 * Form for display/setting of 'download' preferences
 * @package Forms
 */
class Form_Preferences_Download extends Form_Preferences
{

    protected $_types = array(
        'PackageDeployment' => 'bool',
        'DownloadPeriodDelay' => 'integer',
        'DownloadCycleDelay' => 'integer',
        'DownloadFragmentDelay' => 'integer',
        'DownloadMaxPriority' => 'integer',
        'DownloadTimeout' => 'integer',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'PackageDeployment' => $translate->_(
                'Enable package download'
            ),
            'DownloadPeriodDelay' => $translate->_(
                'Delay (in seconds) between periods'
            ),
            'DownloadCycleDelay' => $translate->_(
                'Delay (in seconds) between cycles'
            ),
            'DownloadFragmentDelay' => $translate->_(
                'Delay (in seconds) between fragments'
            ),
            'DownloadMaxPriority' => $translate->_(
                'Maximum package priority (packages with higher value will not be downloaded)'
            ),
            'DownloadTimeout' => $translate->_(
                'Timeout (in days)'
            ),
        );
        parent::init();
        $this->getElement('DownloadPeriodDelay')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('DownloadCycleDelay')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('DownloadFragmentDelay')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
        $this->getElement('DownloadMaxPriority')
            ->addValidator('Between', false, array('min' => 0, 'max' => 10))
            ->setAttrib('size', '5');
        $this->getElement('DownloadTimeout')
            ->addValidator('GreaterThan', false, array('min' => 0))
            ->setAttrib('size', '5');
    }

}
