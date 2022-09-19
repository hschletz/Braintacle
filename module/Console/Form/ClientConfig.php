<?php

/**
 * Client/group configuration
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

namespace Console\Form;

use Laminas\Form\Element;

/**
 * Client/group configuration
 *
 * This form operates on a particular client or group which must be set via
 * setClientObject().
 */
class ClientConfig extends Form
{
    /**
     * Client or group object for which configuration is shown/set.
     * @var \Model\ClientOrGroup
     */
    protected $_object;

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        // Agent options
        $agent = new \Laminas\Form\Fieldset('Agent');
        $agent->setLabel('Agent');
        $inputFilterAgent = new \Laminas\InputFilter\InputFilter();

        $contactInterval = new Element\Text('contactInterval');
        $contactInterval->setLabel('Agent contact interval (in hours)')
                        ->setAttribute('size', '5');
        $agent->add($contactInterval);
        $inputFilterAgent->add($this->getIntegerFilter('contactInterval', 1));

        $inventoryInterval = new Element\Text('inventoryInterval');
        $inventoryInterval->setLabel('Inventory interval (in days, 0 = always, -1 = never)')
                          ->setAttribute('size', '5');
        $agent->add($inventoryInterval);
        $inputFilterAgent->add($this->getIntegerFilter('inventoryInterval', -1));

        $this->add($agent);
        $inputFilter->add($inputFilterAgent, 'Agent');

        // Download options
        $download = new \Laminas\Form\Fieldset('Download');
        $download->setLabel('Download');
        $inputFilterDownload = new \Laminas\InputFilter\InputFilter();

        $packageDeployment = new Element\Checkbox('packageDeployment');
        $packageDeployment->setLabel('Enable package download');
        $packageDeployment->setAttribute('class', 'toggle');
        $download->add($packageDeployment);

        $downloadPeriodDelay = new Element\Text('downloadPeriodDelay');
        $downloadPeriodDelay->setLabel('Delay (in seconds) between periods')
                            ->setAttribute('size', '5');
        $download->add($downloadPeriodDelay);
        $inputFilterDownload->add($this->getIntegerFilter('downloadPeriodDelay', 1));

        $downloadCycleDelay = new Element\Text('downloadCycleDelay');
        $downloadCycleDelay->setLabel('Delay (in seconds) between cycles')
                            ->setAttribute('size', '5');
        $download->add($downloadCycleDelay);
        $inputFilterDownload->add($this->getIntegerFilter('downloadCycleDelay', 1));

        $downloadFragmentDelay = new Element\Text('downloadFragmentDelay');
        $downloadFragmentDelay->setLabel('Delay (in seconds) between fragments')
                                ->setAttribute('size', '5');
        $download->add($downloadFragmentDelay);
        $inputFilterDownload->add($this->getIntegerFilter('downloadFragmentDelay', 1));

        $downloadMaxPriority = new Element\Text('downloadMaxPriority');
        $downloadMaxPriority->setLabel('Maximum package priority')
                            ->setAttribute('size', '5');
        $download->add($downloadMaxPriority);
        $inputFilterDownload->add($this->getIntegerFilter('downloadMaxPriority', 1));

        $downloadTimeout = new Element\Text('downloadTimeout');
        $downloadTimeout->setLabel('Timeout (in days)')
                        ->setAttribute('size', '5');
        $download->add($downloadTimeout);
        $inputFilterDownload->add($this->getIntegerFilter('downloadTimeout', 1));

        $this->add($download);
        $inputFilter->add($inputFilterDownload, 'Download');

        // Network scanning options
        $scan = new \Laminas\Form\Fieldset('Scan');
        $scan->setLabel('Network scanning');
        $inputFilterScan = new \Laminas\InputFilter\InputFilter();

        $allowScan = new Element\Checkbox('allowScan');
        $allowScan->setLabel('Allow network scanning');
        $allowScan->setAttribute('class', 'toggle');
        $scan->add($allowScan);

        $subnets = new \Library\Form\Element\SelectSimple('scanThisNetwork');
        $subnets->setLabel('Always scan this network')
                ->setEmptyOption('');
        $scan->add($subnets);
        $inputFilterScan->add(array('name' => 'scanThisNetwork', 'required' => false));

        $scanSnmp = new Element\Checkbox('scanSnmp');
        $scanSnmp->setLabel('Use SNMP');
        $scan->add($scanSnmp);

        $this->add($scan);
        $inputFilter->add($inputFilterScan, 'Scan');

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('OK');
        $this->add($submit);

        $this->setInputFilter($inputFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        if (!($this->_object instanceof \Model\ClientOrGroup)) {
            throw new \LogicException('No client or group object set');
        }
        return parent::setData($data);
    }

    /**
     * Get input filter specification for an integer field
     *
     * @param string $name Field name
     * @param integer $min Allowed minimum value
     * @return array
     */
    protected function getIntegerFilter($name, $min)
    {
        $validatorChain = new \Laminas\Validator\ValidatorChain();
        $validatorChain->attachByName(
            'Callback',
            array('callback' => array($this, 'validateInteger')),
            true
        );
        // Callback validators do not support message variables. For
        // translatable messages with a parameter, do translation and
        // substitution here and disable further translation.
        $dummyMinValueValidator = new \Laminas\Validator\GreaterThan();
        $message = $dummyMinValueValidator->getMessageTemplates()[\Laminas\Validator\GreaterThan::NOT_GREATER_INCLUSIVE];
        $minValueValidator = new \Laminas\Validator\Callback();
        $minValueValidator->setCallback(array($this, 'validateMinValue'))
                          ->setCallbackOptions($min)
                          ->setMessage(
                              str_replace(
                                  '%min%',
                                  $min,
                                  $minValueValidator->getTranslator()->translate($message)
                              )
                          )
                          ->setTranslatorEnabled(false);
        $validatorChain->attach($minValueValidator);
        return array(
            'name' => $name,
            'required' => false,
            'filters' => array(
                array(
                    'name' => 'Callback',
                    'options' => array('callback' => array($this, 'filterInteger'))
                ),
            ),
            'validators' => $validatorChain,
        );
    }

    /**
     * Integer filter callback
     * @internal
     */
    public function filterInteger($value)
    {
        return $this->normalize($value, 'integer');
    }

    /**
     * Integer validator callback
     * @internal
     */
    public function validateInteger($value, $context)
    {
        if (isset($context['packageDeployment']) and !$context['packageDeployment']) {
            // Ignore value if checkbox is unchecked
            return true;
        } else {
            return $this->validateType($value, $context, 'integer');
        }
    }

    /**
     * Integer minimum value validator callback
     * @internal
     */
    public function validateMinValue($value, $context, $min)
    {
        if (isset($context['packageDeployment']) and !$context['packageDeployment']) {
            return true;
        } else {
            return $value >= $min;
        }
    }

    /**
     * Set client/group object on which the form will operate
     *
     * @param \Model\ClientOrGroup $object
     */
    public function setClientObject(\Model\ClientOrGroup $object)
    {
        $this->_object = $object;

        $addresses = array();
        if ($object instanceof \Model\Client\Client) {
            // Get list of all networks this client is connected to
            $interfaces = $object->getItems('NetworkInterface', 'Subnet');
            foreach ($interfaces as $interface) {
                $subnet = $interface['Subnet'];
                // Exclude duplicates and non-scannable networks
                if ($subnet != '0.0.0.0' and !in_array($subnet, $addresses)) {
                    $addresses[] = $subnet;
                }
            }
        }
        $this->get('Scan')->get('scanThisNetwork')->setValueOptions($addresses)
                                                  ->setAttribute('disabled', !$addresses);
    }

    /**
     * Get client/group object on which the form will operate
     *
     * @return \Model\ClientOrGroup
     */
    public function getClientObject()
    {
        return $this->_object;
    }

    /**
     * Apply the entered settings to the client or group
     */
    public function process()
    {
        $data = $this->getData();
        $this->processFieldset($data['Agent']);
        $this->processFieldset($data['Download'], 'packageDeployment');
        $this->processFieldset($data['Scan'], 'allowScan');
    }

    /**
     * Apply the settings of a fieldset
     *
     * @param array $data Fieldset data
     * @param string $masterElement Optional name of a checkbox that voids all other elements if unchecked.
     */
    protected function processFieldset($data, $masterElement = null)
    {
        if ($masterElement) {
            $clearValues = !$data[$masterElement];
        } else {
            $clearValues = false;
        }
        foreach ($data as $option => $value) {
            if ($value == '' or ($clearValues and $option != $masterElement)) {
                $value = null;
            }
            $this->_object->setConfig($option, $value);
        }
    }
}
