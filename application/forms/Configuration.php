<?php
/**
 * Form for computer/group configuration
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
 * Form for computer/group configuration
 *
 * This form operates on a particular computer or group which must be passed to
 * the constructor as the 'object' option.
 * @package Forms
 */
class Form_Configuration extends Zend_Form
{
    /**
     * Computer or group object for which configuration is shown/set.
     * @var Model_ComputerOrGroup
     */
    public $object;

    /**
     * Global translate object
     * @var Zend_Translate
     */
    protected $_translate;

    /**
     * @ignore
     */
    public function init()
    {
        // This MUST be passed to the constructor.
        if (!$this->object instanceof Model_ComputerOrGroup) {
            throw new LogicException(__CLASS__ . ' has invalid "object" property.');
        }

        $this->addElementPrefixPath('Zend', \Library\Application::$zf1Path);
        $this->_translate = Zend_Registry::get('Zend_Translate');

        // Agent options. Always present.
        $contactInterval = new Zend_Form_Element_Text('contactInterval');
        $contactInterval->setLabel('Agent contact interval (in hours)')
                        ->addValidator('Int', false, array('options' =>'locale'))
                        ->addValidator('GreaterThan', false, array('min' => 0))
                        ->setAttrib('size', '5')
                        ->setValue($this->object->getConfig('contactInterval'));
        $this->_setElementDecorators($contactInterval);
        $inventoryInterval = new Zend_Form_Element_Text('inventoryInterval');
        $inventoryInterval->setLabel('Inventory interval (in days, 0 = always, -1 = never)')
                          ->addValidator('Int', false, array('options' =>'locale'))
                          ->addValidator('GreaterThan', false, array('min' => -2))
                          ->setAttrib('size', '5')
                          ->setValue($this->object->getConfig('inventoryInterval'));
        $this->_setElementDecorators($inventoryInterval);
        $agent = new Zend_Form_SubForm(array('class' => 'fieldset-fullwidth'));
        $agent->setLegend('Agent')
              ->addElement($contactInterval)
              ->addElement($inventoryInterval);
        $this->addSubForm($agent, 'Agent');

        // Download options. Not present if globally disabled.
        if ($this->object->getDefaultConfig('packageDeployment')) {
            $packageDeployment = new Zend_Form_Element_Checkbox('packageDeployment');
            $packageDeployment->setLabel('Enable package download')
                              ->setAttrib('onchange', 'toggle(this, "download_option");')
                              ->setChecked($this->object->getConfig('packageDeployment') === null);
            $this->_setElementDecorators($packageDeployment);
            // The following elements are displayed or hidden depending on the
            // state of the previous checkbox.
            $downloadPeriodDelay = new Zend_Form_Element_Text('downloadPeriodDelay');
            $downloadPeriodDelay->setLabel('Delay (in seconds) between periods')
                                ->addValidator('Int', false, array('options' =>'locale'))
                                ->addValidator('GreaterThan', false, array('min' => 0))
                                ->setAttrib('size', '5')
                                ->setValue($this->object->getConfig('downloadPeriodDelay'));
            $this->_setElementDecorators($downloadPeriodDelay, 'download_option');
            $downloadCycleDelay = new Zend_Form_Element_Text('downloadCycleDelay');
            $downloadCycleDelay->setLabel('Delay (in seconds) between cycles')
                                ->addValidator('Int', false, array('options' =>'locale'))
                                ->addValidator('GreaterThan', false, array('min' => 0))
                                ->setAttrib('size', '5')
                                ->setValue($this->object->getConfig('downloadCycleDelay'));
            $this->_setElementDecorators($downloadCycleDelay, 'download_option');
            $downloadFragmentDelay = new Zend_Form_Element_Text('downloadFragmentDelay');
            $downloadFragmentDelay->setLabel('Delay (in seconds) between fragments')
                                ->addValidator('Int', false, array('options' =>'locale'))
                                ->addValidator('GreaterThan', false, array('min' => 0))
                                ->setAttrib('size', '5')
                                ->setValue($this->object->getConfig('downloadFragmentDelay'));
            $this->_setElementDecorators($downloadFragmentDelay, 'download_option');
            $downloadMaxPriority = new Zend_Form_Element_Text('downloadMaxPriority');
            $downloadMaxPriority
                ->setLabel(
                    'Maximum package priority (packages with higher value will not be downloaded)'
                )
                ->addValidator('Int', false, array('options' =>'locale'))
                ->addValidator('GreaterThan', false, array('min' => 0))
                ->setAttrib('size', '5')
                ->setValue($this->object->getConfig('downloadMaxPriority'));
            $this->_setElementDecorators($downloadMaxPriority, 'download_option');
            $downloadTimeout = new Zend_Form_Element_Text('downloadTimeout');
            $downloadTimeout->setLabel('Timeout (in days)')
                            ->addValidator('Int', false, array('options' =>'locale'))
                            ->addValidator('GreaterThan', false, array('min' => 0))
                            ->setAttrib('size', '5')
                            ->setValue($this->object->getConfig('downloadTimeout'));;
            $this->_setElementDecorators($downloadTimeout, 'download_option');
            $download = new Zend_Form_SubForm(array('class' => 'fieldset-fullwidth'));
            $download->setLegend('Download')
                    ->addElement($packageDeployment)
                    ->addElement($downloadPeriodDelay)
                    ->addElement($downloadCycleDelay)
                    ->addElement($downloadFragmentDelay)
                    ->addElement($downloadMaxPriority)
                    ->addElement($downloadTimeout);
            $this->addSubForm($download, 'Download');
        }

        // Network scanning options. Not present if globally disabled.
        if ($this->object->getDefaultConfig('allowScan')) {
            $scan = new Zend_Form_SubForm(array('class' => 'fieldset-fullwidth'));
            $scan->setLegend('Network scanning');
            $allowScan = new Zend_Form_Element_Checkbox('allowScan');
            $allowScan->setLabel('Allow network scanning')
                      ->setChecked($this->object->getConfig('allowScan') === null)
                      ->setAttrib('onchange', 'toggle(this, "scan_option");');
            $scan->addElement($allowScan);
            $this->_setElementDecorators($allowScan);

            // The following elements are displayed or hidden depending on the
            // state of the previous checkbox and individual conditions.

            // Select network to scan explicitly. Not available for groups.
            if ($this->object instanceof Model_Computer) {
                // Get list of all networks this computer is connected to
                $addresses = array('' => ''); // Empty default entry for dropdown
                $interfaces = $this->object->getChildObjects(
                    'NetworkInterface',
                    'Subnet'
                );
                while ($interface = $interfaces->fetchObject('Model_NetworkInterface')) {
                    $subnet = $interface->getSubnet();
                    // Filter duplicates and non-scannable networks
                    if ($subnet != '0.0.0.0' and !in_array($subnet, $addresses)) {
                        $addresses[$subnet] = $subnet;
                    }
                }
                // Create Dropdown only if networks are available, i.e.
                // $addresses contains more than the empty default entry.
                if (count($addresses) > 1) {
                    $subnets = new Zend_Form_Element_Select('scanThisNetwork');
                    $subnets->setLabel($this->_translate->_('Always scan this network'))
                            ->setDisableTranslator(true)
                            ->setMultiOptions($addresses)
                            ->setValue($this->object->getConfig('scanThisNetwork'));
                    $this->_setElementDecorators($subnets, 'scan_option', false);
                    $scan->addElement($subnets);
                }
            }
            if ($this->object->getDefaultConfig('scanSnmp')) {
                $scanSnmp = new Zend_Form_Element_Checkbox('scanSnmp');
                $scanSnmp->setLabel('Use SNMP')
                         ->setChecked($this->object->getConfig('scanSnmp') === null);
                $this->_setElementDecorators($scanSnmp, 'scan_option');
                $scan->addElement($scanSnmp);
            }
            $this->addSubForm($scan, 'Scan');
        }

        $submit = new Zend_Form_Element_Submit('OK');
        $submit->setLabel('OK')
               ->setDecorators(
                   array(
                       'ViewHelper',
                       array(
                           'HtmlTag',
                           array('tag' => 'p', 'class' => 'textcenter'),
                        ),
                    )
               );
        $this->addElement($submit);

        $this->setSubFormDecorators(
            array(
                'FormElements',
                array('HtmlTag', array('tag' => 'dl')),
                'Fieldset'
            )
        );
        $this->setDecorators(array('FormElements', 'Form'));
    }

    /**
     * Set computer or group for which configuration is shown/set.
     *
     * @param Model_ComputerOrGroup $object Computer or group object
     */
    public function setObject(Model_ComputerOrGroup $object)
    {
        $this->object = $object;
    }

    /**
     * @ignore
     * Decorator callback to render the global default of an option (derived from element name).
     * For computers, the effective value is printed too.
     */
    public function defaultValueDecorator($content, $element, array $options)
    {
        $default = $this->object->getDefaultConfig($element->getName());
        $output  = '(' . $this->_translate->_('Default') .': ';
        if ($element instanceof Zend_Form_Element_Checkbox) {
            if ($default) {
                $output .= $this->_translate->_('Yes');
            } else {
                $output .= $this->_translate->_('No');
            }
        } else {
            $output .= $default;
        }
        if ($this->object instanceof Model_Computer) {
            $effective = $this->object->getEffectiveConfig($element->getName());
            $output .= ', ' . $this->_translate->_('Effective') . ': ';
            if ($element instanceof Zend_Form_Element_Checkbox) {
                if ($effective) {
                    $output .= $this->_translate->_('Yes');
                } else {
                    $output .= $this->_translate->_('No');
                }
            } else {
                $output .= $effective;
            }
        }
        $output .= ')';
        return $output;
    }

    /**
     * Modify an element's default decorator set
     *
     * @param Zend_Form_Element $element Element to decorate
     * @param string $class Optional: class name for dt and dd tags
     * @param bool $addDefault If true (default), add the defaultValueDecorator()
     */
    protected function _setElementDecorators(Zend_Form_Element $element, $class=null, $addDefault=true)
    {
        $decorators = $element->getDecorators();
        if ($addDefault) {
            // Insert defaultValueDecorator at the beginning of the decorator chain
            array_unshift(
                $decorators,
                new Zend_Form_Decorator_Callback(
                    array(
                        'callback' => array(
                            $this,
                            'defaultValueDecorator'
                        )
                    )
                )
            );
            // Since ViewHelper is called afterwards, fix display order
            $decorators['Zend_Form_Decorator_ViewHelper']->setOption('placement', 'prepend');
        }
        if ($class) {
            $decorators['Zend_Form_Decorator_HtmlTag']->setOption('class', $class); // <dd>
            $decorators['Zend_Form_Decorator_Label']->setTagClass($class); // <dt>
        }
        $element->setDecorators($decorators);
    }

    /**
     * @ignore
     */
    public function render(Zend_View_Interface $view=null)
    {
        $view = $this->getView();
        $view->headScript()->captureStart();
        ?>

        /**
         * Toggle display of dependent elements (identified by className) by
         * state of checkbox element.
         */
        function toggle(element, className)
        {
            if (!element) {
                return;
            }
            var display;
            if (element.checked) {
                display = 'block';
            } else {
                display = 'none';
            }
            var elements = document.getElementsByClassName(className);
            for (var i = 0; i < elements.length; i++) {
                elements[i].style.display = display;
            }
        }

        /**
         * Called by body.onload().
         */
        function init()
        {
            toggle(document.getElementById('Download-packageDeployment'), 'download_option');
            toggle(document.getElementById('Scan-allowScan'), 'scan_option');
        }

        <?php
        $view->headScript()->captureEnd();

        return parent::render($view);
    }

    /**
     * Apply the entered settings to the computer or group
     */
    public function process()
    {
        $this->_processSubForm('Agent');
        $this->_processSubForm('Download', 'packageDeployment');
        $this->_processSubForm('Scan', 'allowScan');
    }

    /**
     * Apply the settings of a subform
     *
     * @param string $name Subform name
     * @param string $masterElement Optional name of a checkbox that voids all other elements if unchecked.
     */
    protected function _processSubForm($name, $masterElement=null)
    {
        $form = $this->getSubForm($name);
        // Some subforms may not exist if disabled globally or by group.
        if (!$form) {
            return;
        }
        $elements = $form->getElements();
        if ($masterElement) {
            $clearValues = !$elements[$masterElement]->isChecked();
        } else {
            $clearValues = false;
        }
        foreach ($elements as $name => $element) {
            if ($name == $masterElement) {
                $this->_setOption($name, $element, false);
            } else {
                $this->_setOption($name, $element, $clearValues);
            }
        }
    }

    /**
     * Set an option from the element's value
     *
     * @param string $name Option name
     * @param Zend_Form_Element Element to evaluate. If value is '', unset option
     * @param bool $clearValue If true, always unset option instead of evaluating element
     */
    protected function _setOption($name, Zend_Form_Element $element, $clearValue=false)
    {
        if ($clearValue) {
            $value = null;
        } else {
            $value = $element->getValue();
            if ($value == '') {
                $value = null;
            }
        }
        $this->object->setConfig($name, $value);
    }
}
