<?php

/**
 * Form for display/setting of 'network scanning' preferences
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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
 */

namespace Console\Form\Preferences;

/**
 * Form for display/setting of 'network scanning' preferences
 */
class NetworkScanning extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $scannersPerSubnet = new \Laminas\Form\Element\Text('scannersPerSubnet');
        $scannersPerSubnet->setLabel('Number of scanning clients per subnet')
                          ->setAttribute('size', 5);
        $preferences->add($scannersPerSubnet);
        $inputFilter->add($this->getIntegerFilter('scannersPerSubnet', -1));

        $scanSnmp = new \Laminas\Form\Element\Checkbox('scanSnmp');
        $scanSnmp->setLabel('Use SNMP');
        $preferences->add($scanSnmp);

        $scannerMinDays = new \Laminas\Form\Element\Text('scannerMinDays');
        $scannerMinDays->setLabel('Minimum days before a scanning client is replaced')
                       ->setAttribute('size', 5);
        $preferences->add($scannerMinDays);
        $inputFilter->add($this->getIntegerFilter('scannerMinDays', 0));

        $scannerMaxDays = new \Laminas\Form\Element\Text('scannerMaxDays');
        $scannerMaxDays->setLabel('Maximum days before a scanning client is replaced')
                       ->setAttribute('size', 5);
        $preferences->add($scannerMaxDays);
        $inputFilter->add($this->getIntegerFilter('scannerMaxDays', 0));

        $scanArpDelay = new \Laminas\Form\Element\Text('scanArpDelay');
        $scanArpDelay->setLabel('Delay (in milliseconds) between ARP requests')
                     ->setAttribute('size', 5);
        $preferences->add($scanArpDelay);
        $inputFilter->add($this->getIntegerFilter('scanArpDelay', 9));

        $parentFilter = new \Laminas\InputFilter\InputFilter();
        $parentFilter->add($inputFilter, 'Preferences');
        $this->setInputFilter($parentFilter);
    }

    /**
     * Create input filter specification for integers
     *
     * @param string $name Element name
     * @param integer $min 'min' option to passt to GreaterThan validator
     * @return array
     */
    protected function getIntegerFilter($name, $min)
    {
        $validatorChain = new \Laminas\Validator\ValidatorChain();
        $validatorChain->attachByName(
            'Callback',
            array(
                'callback' => array($this, 'validateType'),
                'callbackOptions' => 'integer',
            ),
            true
        );
        $validatorChain->attachByName('GreaterThan', array('min' => $min));
        return array(
            'name' => $name,
            'filters' => array(
                array(
                    'name' => 'Callback',
                    'options' => array(
                        'callback' => array($this, 'normalize'),
                        'callback_params' => 'integer',
                    ),
                ),
            ),
            'validators' => $validatorChain,
        );
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['Preferences']['scannersPerSubnet'] = $this->localize(
            $data['Preferences']['scannersPerSubnet'],
            'integer'
        );
        $data['Preferences']['scannerMinDays'] = $this->localize(
            $data['Preferences']['scannerMinDays'],
            'integer'
        );
        $data['Preferences']['scannerMaxDays'] = $this->localize(
            $data['Preferences']['scannerMaxDays'],
            'integer'
        );
        $data['Preferences']['scanArpDelay'] = $this->localize(
            $data['Preferences']['scanArpDelay'],
            'integer'
        );
        return parent::setData($data);
    }
}
