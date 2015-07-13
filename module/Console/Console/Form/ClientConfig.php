<?php
/**
 * Computer/group configuration
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

use Zend\Form\Element;

/**
 * Computer/group configuration
 *
 * This form operates on a particular computer or group which must be set via
 * setClientObject().
 */
class ClientConfig extends Form
{
    /**
     * Computer or group object for which configuration is shown/set.
     * @var \Model\ClientOrGroup
     */
    protected $_object;

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $inputFilter = new \Zend\InputFilter\InputFilter;

        // Agent options
        $agent = new \Zend\Form\Fieldset('Agent');
        $inputFilterAgent = new \Zend\InputFilter\InputFilter;

        $contactInterval = new Element\Text('contactInterval');
        $contactInterval->setLabel('Agent contact interval (in hours)')
                        ->setAttribute('size', '5');
        $agent->add($contactInterval);
        $inputFilterAgent->add($this->_getIntegerFilter('contactInterval', 1));

        $inventoryInterval = new Element\Text('inventoryInterval');
        $inventoryInterval->setLabel('Inventory interval (in days, 0 = always, -1 = never)')
                          ->setAttribute('size', '5');
        $agent->add($inventoryInterval);
        $inputFilterAgent->add($this->_getIntegerFilter('inventoryInterval', -1));

        $this->add($agent);
        $inputFilter->add($inputFilterAgent, 'Agent');

        // Download options
        $download = new \Zend\Form\Fieldset('Download');
        $inputFilterDownload = new \Zend\InputFilter\InputFilter;

        $packageDeployment = new Element\Checkbox('packageDeployment');
        $packageDeployment->setLabel('Enable package download')
                            ->setAttribute('onchange', 'toggle(this)');
        $download->add($packageDeployment);

        $downloadPeriodDelay = new Element\Text('downloadPeriodDelay');
        $downloadPeriodDelay->setLabel('Delay (in seconds) between periods')
                            ->setAttribute('size', '5');
        $download->add($downloadPeriodDelay);
        $inputFilterDownload->add($this->_getIntegerFilter('downloadPeriodDelay', 1));

        $downloadCycleDelay = new Element\Text('downloadCycleDelay');
        $downloadCycleDelay->setLabel('Delay (in seconds) between cycles')
                            ->setAttribute('size', '5');
        $download->add($downloadCycleDelay);
        $inputFilterDownload->add($this->_getIntegerFilter('downloadCycleDelay', 1));

        $downloadFragmentDelay = new Element\Text('downloadFragmentDelay');
        $downloadFragmentDelay->setLabel('Delay (in seconds) between fragments')
                                ->setAttribute('size', '5');
        $download->add($downloadFragmentDelay);
        $inputFilterDownload->add($this->_getIntegerFilter('downloadFragmentDelay', 1));

        $downloadMaxPriority = new Element\Text('downloadMaxPriority');
        $downloadMaxPriority->setLabel('Maximum package priority')
                            ->setAttribute('size', '5');
        $download->add($downloadMaxPriority);
        $inputFilterDownload->add($this->_getIntegerFilter('downloadMaxPriority', 1));

        $downloadTimeout = new Element\Text('downloadTimeout');
        $downloadTimeout->setLabel('Timeout (in days)')
                        ->setAttribute('size', '5');
        $download->add($downloadTimeout);
        $inputFilterDownload->add($this->_getIntegerFilter('downloadTimeout', 1));

        $this->add($download);
        $inputFilter->add($inputFilterDownload, 'Download');

        // Network scanning options
        $scan = new \Zend\Form\Fieldset('Scan');
        $inputFilterScan = new \Zend\InputFilter\InputFilter;

        $allowScan = new Element\Checkbox('allowScan');
        $allowScan->setLabel('Allow network scanning')
                  ->setAttribute('onchange', 'toggle(this)');
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
            throw new \LogicException('No computer or group object set');
        }
        return parent::setData($data);
    }

    /** {@inheritdoc} */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $view->headScript()->captureStart();
        ?>

        // Hide or show all label elements following the checkbox within the same fieldset
        function toggle(element)
        {
            var node = element.parentNode.parentNode;
            while (node = node.nextSibling) {
                switch (node.nodeName) {
                    case 'LABEL':
                        node.style.display = element.checked ? 'table-row' : 'none';
                        break;
                    case 'UL':
                        node.style.display = element.checked ? 'block' : 'none';
                        break;
                    default:
                        // Invisible text node, nothing to be changed
                }
            }
        }

        // Initialize display of fieldset content
        function toggleByName(name)
        {
            var elements = document.getElementsByName(name);
            for (var i = elements.length - 1; i >= 0; i--) {
                if (elements[i].type == 'checkbox') {
                    toggle(elements[i]);
                    break;
                }
            }
        }

        <?php
        $view->headScript()->captureEnd();
        $view->placeholder('BodyOnLoad')->append('toggleByName("Download[packageDeployment]")');
        $view->placeholder('BodyOnLoad')->append('toggleByName("Scan[allowScan]")');

        return parent::render($view);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset)
    {
        $name = $fieldset->getName();
        if ($name) {
            $default = $view->translate('Default');
            $effective = $view->translate('Effective');
            $yes = $view->translate('Yes');
            $no = $view->translate('No');
            switch ($name) {
                case 'Agent':
                    $legend = $view->translate('Agent');
                    break;
                case 'Download':
                    $legend = $view->translate('Download');
                    break;
                case 'Scan':
                    $legend = $view->translate('Network scanning');
                    break;
            }

            $output = "<div class='table'>\n";
            foreach ($fieldset as $element) {
                if ($element->getAttribute('disabled')) {
                    continue;
                }
                preg_match('/.*\[(.*)\]$/', $element->getName(), $matches);
                $option = $matches[1];
                if ($option == 'scanThisNetwork') {
                    $row = '';
                } else {
                    $defaultValue = $this->_object->getDefaultConfig($option);
                    if ($element instanceof Element\Checkbox) {
                        $defaultValue = $defaultValue ? $yes : $no;
                    }
                    $row = sprintf('%s: %s', $default, $defaultValue);
                    if ($this->_object instanceof \Model_Computer) {
                        $effectiveValue = $this->_object->getEffectiveConfig($option);
                        if ($element instanceof Element\Checkbox) {
                            $effectiveValue = $effectiveValue ? $yes : $no;
                        }
                        $row .= sprintf(', %s: %s', $effective, $effectiveValue);
                    }
                    $row = $view->escapeHtml("($row)");
                }
                if ($element->getMessages()) {
                    $element->setAttribute('class', 'input-error');
                }
                $row = $view->htmlTag('span', $view->formElement($element) . $row, array('class' => 'values'));
                $row = $view->htmlTag('span', $view->translate($element->getLabel()), array('class' => 'label')) . $row;
                $output .= $view->htmlTag('label', $row);
                if ($element->getMessages()) {
                    $output .= $view->htmlTag('span', null, array('class' => 'cell'));
                    $output .= $view->formElementErrors($element, array('class' => 'error'));
                }
            }
            $output .= "</div>\n";
            $output = $view->htmlTag(
                'fieldset',
                $view->htmlTag('legend', $legend) . $output
            );
        } else {
            $output = "<div class='table'>\n";
            foreach ($fieldset as $element) {
                if ($element instanceof \Zend\Form\Fieldset) {
                    $output .= $this->renderFieldset($view, $element);
                }
            }
            $output .= $view->formRow($fieldset->get('Submit'));
            $output .= "</div>\n";
        }
        return $output;
    }

    /**
     * Get input filter specification for an integer field
     *
     * @param string $name Field name
     * @param integer $min Allowed minimum value
     * @return array
     */
    protected function _getIntegerFilter($name, $min)
    {
        $validatorChain = new \Zend\Validator\ValidatorChain;
        $validatorChain->attachByName(
            'Callback',
            array('callback' => array($this, 'validateInteger')),
            true
        );
        $minValueValidator = new \Zend\Validator\Callback;
        $minValueValidator->setCallback(array($this, 'validateMinValue'))
                          ->setCallbackOptions($min)
                          ->setMessage("The input is not greater or equal than '$min'");
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
     * Set computer/group object on which the form will operate
     *
     * @param \Model\ClientOrGroup $object
     */
    public function setClientObject(\Model\ClientOrGroup $object)
    {
        $this->_object = $object;

        $addresses = array();
        if ($object instanceof \Model_Computer) {
            // Get list of all networks this computer is connected to
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
     * Apply the entered settings to the computer or group
     */
    public function process()
    {
        $data = $this->getData();
        $this->_processFieldset($data['Agent']);
        $this->_processFieldset($data['Download'], 'packageDeployment');
        $this->_processFieldset($data['Scan'], 'allowScan');
    }

    /**
     * Apply the settings of a fieldset
     *
     * @param array $data Fieldset data
     * @param string $masterElement Optional name of a checkbox that voids all other elements if unchecked.
     */
    protected function _processFieldset($data, $masterElement=null)
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
