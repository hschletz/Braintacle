<?php

/**
 * Form for display/setting of 'filters' preferences
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
 * Form for display/setting of 'filters' preferences
 */
class Filters extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $trustedNetworksOnly = new \Laminas\Form\Element\Checkbox('trustedNetworksOnly');
        $trustedNetworksOnly->setLabel('Limit agent connections to trusted networks');
        $preferences->add($trustedNetworksOnly);

        $inventoryFilter = new \Laminas\Form\Element\Checkbox('inventoryFilter');
        $inventoryFilter->setLabel('Limit inventory frequency');
        $preferences->add($inventoryFilter);

        $limitInventoryInterval = new \Laminas\Form\Element\Text('limitInventoryInterval');
        $limitInventoryInterval->setLabel('Seconds between inventory processing')
                               ->setAttribute('size', 5);
        $preferences->add($limitInventoryInterval);
        $validatorChain = new \Laminas\Validator\ValidatorChain();
        $validatorChain->attachByName(
            'Callback',
            array(
                'callback' => array($this, 'validateType'),
                'callbackOptions' => 'integer',
            ),
            true
        );
        $validatorChain->attachByName(
            'GreaterThan',
            array('min' => 0)
        );
        $inputFilter->add(
            array(
                'name' => 'limitInventoryInterval',
                'required' => false,
                'filters' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'normalize'),
                            'callback_params' => 'integer',
                        )
                    )
                ),
                'validators' => $validatorChain,
            )
        );

        $parentFilter = new \Laminas\InputFilter\InputFilter();
        $parentFilter->add($inputFilter, 'Preferences');
        $this->setInputFilter($parentFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['Preferences']['limitInventoryInterval'] = $this->localize(
            $data['Preferences']['limitInventoryInterval'],
            'integer'
        );
        return parent::setData($data);
    }
}
