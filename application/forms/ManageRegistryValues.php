<?php
/**
 * Form for defining and deleting inventoried registry values
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * Form for defining and deleting inventoried registry values
 * @package Forms
 */
class Form_ManageRegistryValues extends Zend_Form
{

    /**
     * Array of all values defined in the database
     * @var array[Model_RegistryValue]
     **/
    protected $_definedValues = array();

    /**
     * @ignore
     **/
    public function init()
    {
        $this->setMethod('post');
        $translate = Zend_Registry::get('Zend_Translate');

        // Subform for enabling/disabling registry inspection, in addition to
        // the same setting in preferences.
        $subFormInspect = new Zend_Form_SubForm;
        $subFormInspect->setDecorators(array())
                       ->setElementsBelongTo('inspect'); // Mysterious tweak to make it work...
        $inspect = new Zend_Form_Element_Checkbox('inspect');
        $inspect->setLabel('Inspect registry')
                ->setChecked(Model_Config::get('InspectRegistry'));
        $subFormInspect->addElement($inspect);
        $this->addSubForm($subFormInspect, 'inspect');

        // Elements for existing values
        $subFormExisting = new Zend_Form_SubForm();
        $subFormExisting->setDecorators(array());
        $this->addSubForm($subFormExisting, 'existing');
        $this->_getDefinedValues();

        $subFormNew = new Zend_Form_SubForm();
        $subFormNew->setDecorators(array('FormElements'));

        $newName = new Zend_Form_Element_Text('name');
        $newName->setLabel('Name')
                ->addFilter('StringTrim')
                ->addValidator('StringLength', false, array(0, 255))
                ->addValidator($this->_createBlacklistValidator());
        $subFormNew->addElement($newName);

        $newRootKey = new Zend_Form_Element_Select('rootKey');
        $newRootKey->setDisableTranslator(true)
                   ->setLabel($translate->_('Root key'))
                   ->setMultiOptions(Model_RegistryValue::rootKeys());
        $subFormNew->addElement($newRootKey);

        // Additional validation in isValid()
        $newSubKeys = new Zend_Form_Element_Text('subKeys');
        $newSubKeys->setLabel('Subkeys')
                   ->addFilter('StringTrim')
                   ->addValidator('StringLength', false, array(1, 255));
        $subFormNew->addElement($newSubKeys);

        $newValue = new Zend_Form_Element_Text('value');
        $newValue->setLabel('Only this value (optional)')
                 ->addFilter('StringTrim')
                 ->addValidator('StringLength', false, array(0, 255));
        $subFormNew->addElement($newValue);

        $this->addSubForm($subFormNew, 'newValue');

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel('Change');
        $this->addElement($submit);

        // Set defaults for new value
        $this->resetNewValue();
    }

    /**
     * @ignore
     */
    public function isValid($data)
    {
        if ($data['newValue']['name']) {
            // Only required if a new key is defined
            $this->newValue->subKeys->setRequired(true);
        } else {
            $this->newValue->subKeys->setRequired(false);
        }
        return parent::isValid($data);
    }

    /**
     * @ignore
     */
    public function render(Zend_View_Interface $view=null)
    {
        if (!$view) {
            $view = $this->getView();
        }
        $output = '';

        // Create table with rows for each existing field
        $subFormExisting = $this->getSubForm('existing');
        foreach ($this->_definedValues as $value) {
            $id = $value->getId();
            $element = $subFormExisting->getElement("value_{$id}_name");
            $errors =$element->getMessages();

            // Column 1: Form element and validation error messages
            $row = $view->htmlTag(
                'td',
                $view->formText($element->getName(), $element->getValue()) .
                (empty($errors) ? '' : $view->formErrors($errors))
            );
            // Column 2: Description (text representation of value)
            $row .= $view->htmlTag('td', $view->escape($element->getDescription()));
            // Column 3: Link to delete value
            $row .= $view->htmlTag(
                'td',
                $view->htmlTag(
                    'a',
                    $view->translate('Delete'),
                    array(
                        'href' => $view->url(
                            array(
                                'controller' => 'preferences',
                                'action' => 'deleteregistryvalue',
                                'id' => $id,
                            )
                        )
                    )
                )
            );
            $output .= $view->htmlTag('tr', $row);

        }
        $output = $view->htmlTag('table', $output, array('class' => 'table_registry_values'));

        $subFormInspect = $this->getSubForm('inspect');
        $output = $this->renderHtmlTag($subFormInspect->inspect->render($view)) . $output;

        $output .= $view->htmlTag('h3', $view->translate('Add'), array('class' => 'textcenter'));

        // Render remaining elements (those with decorators) in the usual <dl> block
        $output .= $this->renderHtmlTag($this->renderFormelements());

        // Render <form> tag
        $output = $this->renderForm($output);

        return $output;
    }

    /**
     * Get existing values from database
     *
     * This sets up $_definedValues and create Elements to edit their names.
     * This may be called more than once - no duplicates will be created.
     **/
    protected function _getDefinedValues()
    {
        // Create list of values
        $this->_definedValues = array();
        $statement = Model_RegistryValue::createStatementStatic();
        while ($value = $statement->fetchObject('Model_RegistryValue')) {
            $this->_definedValues[] = $value;
        }

        // Remove existing elements
        $subFormExisting = $this->getSubForm('existing');
        foreach ($subFormExisting->getElements() as $element) {
            if (preg_match('/^value_[0-9]+_name$/', $element->getName())) {
                $subFormExisting->removeElement($element->getName());
            }
        }

        // Create text elements for existing values to rename them
        foreach ($this->_definedValues as $index => $value) {
            $name = $value->getName();
            $element = new Zend_Form_Element_Text("value_{$value->getId()}_name");
            $element->setValue($name)
                    ->clearDecorators() // No decorators - element is rendered manually
                    ->setDescription($value->getValueConfiguredAsString())
                    ->setRequired(true)
                    ->addFilter('StringTrim')
                    ->addValidator('StringLength', false, array(1, 255))
                    ->addValidator($this->_createBlacklistValidator($name))
                    ->setOrder($index); // The element is inserted right before the block for a new value.
            $subFormExisting->addElement($element);
        }
    }

    /**
     * Create a validator that forbids any existing name except the given one
     * @param string $name Existing name to allow (default: none)
     * @return Braintacle_Validate_NotInArray Validator object
     **/
    protected function _createBlacklistValidator($name=null)
    {
        $blacklist = array();
        foreach ($this->_definedValues as $value) {
            if ($name != $value->getName()) {
                $blacklist[] = $value->getName();
            }
        }
        return new Braintacle_Validate_NotInArray(
            $blacklist,
            Braintacle_Validate_NotInArray::CASE_INSENSITIVE
        );
    }

    /**
     * Add and rename values and set 'InspectRegistry' option according to form data
     **/
    public function process()
    {
        Model_Config::set('InspectRegistry', $this->inspect->inspect->isChecked());

        $name = $this->newValue->getValue('name');
        if ($name) {
            Model_RegistryValue::add(
                $name,
                $this->newValue->getValue('rootKey'),
                $this->newValue->getValue('subKeys'),
                $this->newValue->getValue('value')
            );
        }
        $subFormExisting = $this->getSubForm('existing');
        foreach ($this->_definedValues as $value) {
            $value->rename(
                $subFormExisting->getValue("value_{$value->getId()}_name")
            );
        }
        // Update list of defined values
        $this->_getDefinedValues();
    }

    /**
     * Reset elements for a new value to their defaults
     **/
    public function resetNewValue()
    {
        $this->newValue->name->setValue(null);
        $this->newValue->rootKey->setValue(Model_RegistryValue::HKEY_LOCAL_MACHINE);
        $this->newValue->subKeys->setValue(null);
        $this->newValue->value->setValue(null);
    }

}
