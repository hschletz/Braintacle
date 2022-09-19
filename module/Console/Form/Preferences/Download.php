<?php

/**
 * Form for display/setting of 'download' preferences
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
 * Form for display/setting of 'download' preferences
 */
class Download extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $packageDeployment = new \Laminas\Form\Element\Checkbox('packageDeployment');
        $packageDeployment->setLabel('Enable package download');
        $preferences->add($packageDeployment);

        $packagePath = new \Laminas\Form\Element\Text('packagePath');
        $packagePath->setLabel('Package storage directory');
        $preferences->add($packagePath);
        $inputFilter->add(
            array(
                'name' => 'packagePath',
                'validators' => array(
                    array('name' => 'Library\Validator\DirectoryWritable'),
                ),
            )
        );

        $packageBaseUriHttp = new \Laminas\Form\Element\Text('packageBaseUriHttp');
        $packageBaseUriHttp->setLabel('HTTP package base URL');
        $preferences->add($packageBaseUriHttp);
        $inputFilter->add(
            array(
                'name' => 'packageBaseUriHttp',
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array( // Strip scheme and trailing slash if given
                        'name' => 'PregReplace',
                        'options' => array(
                            'pattern' => '#(.*://|/$)#',
                            'replacement' => '',
                        ),
                    ),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateUri'),
                        ),
                    ),
                ),
            )
        );

        $packageBaseUriHttps = new \Laminas\Form\Element\Text('packageBaseUriHttps');
        $packageBaseUriHttps->setLabel('HTTPS package base URL');
        $preferences->add($packageBaseUriHttps);
        $inputFilter->add(
            array(
                'name' => 'packageBaseUriHttps',
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array( // Strip scheme and trailing slash if given
                        'name' => 'PregReplace',
                        'options' => array(
                            'pattern' => '#(.*://|/$)#',
                            'replacement' => '',
                        ),
                    ),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateUri'),
                        ),
                    ),
                ),
            )
        );

        $downloadPeriodDelay = new \Laminas\Form\Element\Text('downloadPeriodDelay');
        $downloadPeriodDelay->setLabel('Delay (in seconds) between periods')
                            ->setAttribute('size', 5);
        $preferences->add($downloadPeriodDelay);
        $inputFilter->add($this->getIntegerFilter('downloadPeriodDelay'));

        $downloadCycleDelay = new \Laminas\Form\Element\Text('downloadCycleDelay');
        $downloadCycleDelay->setLabel('Delay (in seconds) between cycles')
                           ->setAttribute('size', 5);
        $preferences->add($downloadCycleDelay);
        $inputFilter->add($this->getIntegerFilter('downloadCycleDelay'));

        $downloadFragmentDelay = new \Laminas\Form\Element\Text('downloadFragmentDelay');
        $downloadFragmentDelay->setLabel('Delay (in seconds) between fragments')
                              ->setAttribute('size', 5);
        $preferences->add($downloadFragmentDelay);
        $inputFilter->add($this->getIntegerFilter('downloadFragmentDelay'));

        $downloadMaxPriority = new \Library\Form\Element\SelectSimple('downloadMaxPriority');
        $downloadMaxPriority->setLabel('Maximum package priority (packages with higher value will not be downloaded)')
                            ->setValueOptions(range(0, 10));
        $preferences->add($downloadMaxPriority);

        $downloadTimeout = new \Laminas\Form\Element\Text('downloadTimeout');
        $downloadTimeout->setLabel('Timeout (in days)')
                        ->setAttribute('size', 5);
        $preferences->add($downloadTimeout);
        $inputFilter->add($this->getIntegerFilter('downloadTimeout'));

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

    /**
     * URI validator callback
     *
     * @param string $value
     * @return bool
     * @internal
     */
    public function validateUri($value)
    {
        // $value has no scheme part. Apply http:// scheme (also valid for HTTPS
        // URI) and try to construct a valid URI.
        try {
            $uri = new \Laminas\Uri\Http();
            return $uri->parse("http://$value")->isValid();
        } catch (\Exception $e) {
            return false;
        }
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['Preferences']['downloadPeriodDelay'] = $this->localize(
            $data['Preferences']['downloadPeriodDelay'],
            'integer'
        );
        $data['Preferences']['downloadCycleDelay'] = $this->localize(
            $data['Preferences']['downloadCycleDelay'],
            'integer'
        );
        $data['Preferences']['downloadFragmentDelay'] = $this->localize(
            $data['Preferences']['downloadFragmentDelay'],
            'integer'
        );
        $data['Preferences']['downloadTimeout'] = $this->localize(
            $data['Preferences']['downloadTimeout'],
            'integer'
        );
        return parent::setData($data);
    }
}
