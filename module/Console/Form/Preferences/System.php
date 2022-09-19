<?php

/**
 * Form for display/setting of 'system' preferences
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
 * Form for display/setting of 'system' preferences
 */
class System extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $communicationServerUri = new \Laminas\Form\Element\Text('communicationServerUri');
        $communicationServerUri->setLabel('Communication server URI');
        $preferences->add($communicationServerUri);
        $inputFilter->add(
            array(
                'name' => 'communicationServerUri',
                'validators' => array(
                    array(
                        'name' => 'Uri',
                        'options' => array(
                            'uriHandler' => 'Laminas\Uri\Http',
                            'allowRelative' => false,
                        ),
                    )
                )
            )
        );

        $lockValidity = new \Laminas\Form\Element\Text('lockValidity');
        $lockValidity->setLabel('Maximum seconds to lock a client')
                     ->setAttribute('size', 5);
        $preferences->add($lockValidity);
        $inputFilter->add($this->getIntegerFilter('lockValidity'));

        $sessionValidity = new \Laminas\Form\Element\Text('sessionValidity');
        $sessionValidity->setLabel('Maximum duration of an agent session in seconds')
                        ->setAttribute('size', 5);
        $preferences->add($sessionValidity);
        $inputFilter->add($this->getIntegerFilter('sessionValidity'));

        $sessionCleanupInterval = new \Laminas\Form\Element\Text('sessionCleanupInterval');
        $sessionCleanupInterval->setLabel('Interval in seconds to cleanup sessions')
                               ->setAttribute('size', 5);
        $preferences->add($sessionCleanupInterval);
        $inputFilter->add($this->getIntegerFilter('sessionCleanupInterval'));

        $sessionRequired = new \Laminas\Form\Element\Checkbox('sessionRequired');
        $sessionRequired->setLabel('Session required for inventory');
        $preferences->add($sessionRequired);

        $logLevel = new \Library\Form\Element\SelectSimple('logLevel');
        $logLevel->setLabel('Log level')
                 ->setValueOptions(array(0, 1, 2));
        $preferences->add($logLevel);

        $validateXml = new \Laminas\Form\Element\Checkbox('validateXml');
        $validateXml->setLabel('Validate XML data');
        $preferences->add($validateXml);

        $autoMergeDuplicates = new \Laminas\Form\Element\Checkbox('autoMergeDuplicates');
        $autoMergeDuplicates->setLabel('Merge duplicates automatically (not recommended)');
        $preferences->add($autoMergeDuplicates);

        $parentFilter = new \Laminas\InputFilter\InputFilter();
        $parentFilter->add($inputFilter, 'Preferences');
        $this->setInputFilter($parentFilter);
    }

    /**
     * Create input filter specification for integers
     *
     * @param string $name Element name
     * @return array
     */
    protected function getIntegerFilter($name)
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
        $validatorChain->attachByName('GreaterThan', array('min' => 0));
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
        $data['Preferences']['lockValidity'] = $this->localize(
            $data['Preferences']['lockValidity'],
            'integer'
        );
        $data['Preferences']['sessionValidity'] = $this->localize(
            $data['Preferences']['sessionValidity'],
            'integer'
        );
        $data['Preferences']['sessionCleanupInterval'] = $this->localize(
            $data['Preferences']['sessionCleanupInterval'],
            'integer'
        );
        return parent::setData($data);
    }
}
