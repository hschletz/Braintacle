<?php
/**
 * Form for display/setting of 'groups' preferences
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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
 * Form for display/setting of 'groups' preferences
 */
class Groups extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $inputFilter = new \Zend\InputFilter\InputFilter;
        $integerFilter = array(
            'name' => 'Callback',
            'options' => array(
                'callback' => array($this, 'normalize'),
                'callback_params' => 'integer',
            )
        );
        $validatorChain = new \Zend\Validator\ValidatorChain;
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

        $groupCacheExpirationInterval = new \Zend\Form\Element\Text('groupCacheExpirationInterval');
        $groupCacheExpirationInterval->setLabel('Minimum seconds between group cache rebuilds')
                                     ->setAttribute('size', 5);
        $preferences->add($groupCacheExpirationInterval);
        $inputFilter->add(
            array(
                'name' => 'groupCacheExpirationInterval',
                'filters' => array($integerFilter),
                'validators' => clone $validatorChain,
            )
        );

        $groupCacheExpirationFuzz = new \Zend\Form\Element\Text('groupCacheExpirationFuzz');
        $groupCacheExpirationFuzz->setLabel('Maximum seconds added to above value')
                                 ->setAttribute('size', 5);
        $preferences->add($groupCacheExpirationFuzz);
        $inputFilter->add(
            array(
                'name' => 'groupCacheExpirationFuzz',
                'filters' => array($integerFilter),
                'validators' => clone $validatorChain,
            )
        );

        $setGroupPackageStatus = new \Zend\Form\Element\Checkbox('setGroupPackageStatus');
        $setGroupPackageStatus->setLabel('Set package status on clients for group-assigned packages');
        $preferences->add($setGroupPackageStatus);

        $parentFilter = new \Zend\InputFilter\InputFilter;
        $parentFilter->add($inputFilter, 'Preferences');
        $this->setInputFilter($parentFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['Preferences']['groupCacheExpirationInterval'] = $this->localize(
            $data['Preferences']['groupCacheExpirationInterval'],
            'integer'
        );
        $data['Preferences']['groupCacheExpirationFuzz'] = $this->localize(
            $data['Preferences']['groupCacheExpirationFuzz'],
            'integer'
        );
        return parent::setData($data);
    }
}
