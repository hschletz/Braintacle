<?php

/**
 * Form for display/setting of 'agent' preferences
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Validator\Callback as CallbackValidator;

/**
 * Form for display/setting of 'agent' preferences
 *
 * @psalm-suppress UnusedClass
 */
class Agent extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $integerFilter = array(
            'name' => 'Callback',
            'options' => array(
                'callback' => array($this, 'normalize'),
                'callback_params' => 'integer',
            )
        );
        $integerValidator = new CallbackValidator([
            'callback' => $this->validateType(...),
            'callbackOptions' => ['integer'],
        ]);

        $contactInterval = new \Laminas\Form\Element\Text('contactInterval');
        $contactInterval->setLabel('Agent contact interval (in hours)')
            ->setAttribute('size', 5);
        $preferences->add($contactInterval);
        $validatorChain = new \Laminas\Validator\ValidatorChain();
        $validatorChain->attach($integerValidator, true)
            ->attachByName('GreaterThan', array('min' => 0));
        $inputFilter->add(
            array(
                'name' => 'contactInterval',
                'filters' => array($integerFilter),
                'validators' => $validatorChain,
            )
        );

        $inventoryInterval = new \Laminas\Form\Element\Text('inventoryInterval');
        $inventoryInterval->setLabel('Inventory interval (in days, 0 = always, -1 = never)')
            ->setAttribute('size', 5);
        $preferences->add($inventoryInterval);
        $validatorChain = new \Laminas\Validator\ValidatorChain();
        $validatorChain->attach($integerValidator, true)
            ->attachByName('GreaterThan', array('min' => -2));
        $inputFilter->add(
            array(
                'name' => 'inventoryInterval',
                'filters' => array($integerFilter),
                'validators' => $validatorChain,
            )
        );

        $agentWhitelistFile = new \Laminas\Form\Element\Text('agentWhitelistFile');
        $agentWhitelistFile->setLabel('File with allowed non-OCS agents (FusionInventory etc.)');
        $preferences->add($agentWhitelistFile);
        $inputFilter->add(
            array(
                'name' => 'agentWhitelistFile',
                'required' => false,
                'validators' => array(
                    array('name' => 'Library\Validator\FileReadable')
                )
            )
        );

        $parentFilter = new \Laminas\InputFilter\InputFilter();
        $parentFilter->add($inputFilter, 'Preferences');
        $this->setInputFilter($parentFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['Preferences']['contactInterval'] = $this->localize(
            $data['Preferences']['contactInterval'],
            'integer'
        );
        $data['Preferences']['inventoryInterval'] = $this->localize(
            $data['Preferences']['inventoryInterval'],
            'integer'
        );
        return parent::setData($data);
    }
}
