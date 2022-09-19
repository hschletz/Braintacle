<?php

/**
 * Form for defining and deleting inventoried registry values
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
 * Form for defining and deleting inventoried registry values
 *
 * The form requires the following options to be set:
 *
 * - **registryManager:** \Model\Registry\RegistryManager instance, required by
 *   init() and process()
 *
 * The factory injects these automatically.
 */
class ManageRegistryValues extends Form
{
    /**
     * Array of all values defined in the database
     * @var \Model\Registry\Value[]
     **/
    protected $_definedValues = array();

    protected function getDefinedValues(): array
    {
        return $this->_definedValues;
    }

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        // Create list of values as array because nested iteration does not work with ResultSet objects.
        $this->_definedValues = iterator_to_array($this->getOption('registryManager')->getValueDefinitions());

        // Subform for existing values
        $fieldsetExisting = new \Laminas\Form\Fieldset('existing');
        $fieldsetExisting->setLabel('Values');
        $inputFilterExisting = new \Laminas\InputFilter\InputFilter();
        // Create text elements for existing values to rename them
        foreach ($this->_definedValues as $value) {
            $name = $value['Name'];
            $elementName = base64_encode($name);
            $element = new Element\Text($elementName);
            $element->setValue($name)
                    ->setLabel($value['FullPath']);
            $inputFilterExisting->add(
                array(
                    'name' => $elementName,
                    'required' => true,
                    'filters' => array(
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name' => 'StringLength',
                            'options' => array('max' => 255)
                        ),
                        $this->createBlacklistValidator($name),
                    ),
                )
            );
            $fieldsetExisting->add($element);
        }
        $this->add($fieldsetExisting);
        $inputFilter->add($inputFilterExisting, 'existing');

        // Subform for new value
        $fieldsetNew = new \Laminas\Form\Fieldset('new_value');
        $fieldsetNew->setLabel('Add');

        $newName = new Element\Text('name');
        $newName->setLabel('Name');
        $fieldsetNew->add($newName);

        $newRootKey = new Element\Select('root_key');
        $newRootKey->setLabel('Root key')
                   ->setAttribute('type', 'select_untranslated')
                   ->setValueOptions(\Model\Registry\Value::rootKeys())
                   ->setValue(\Model\Registry\Value::HKEY_LOCAL_MACHINE);
        $fieldsetNew->add($newRootKey);

        // Additional validation in isValid()
        $newSubKeys = new Element\Text('subkeys');
        $newSubKeys->setLabel('Subkeys');
        $fieldsetNew->add($newSubKeys);

        $newValue = new Element\Text('value');
        $newValue->setLabel('Only this value (optional)');
        $fieldsetNew->add($newValue);

        $this->add($fieldsetNew);

        $submit = new \Library\Form\Element\Submit('submit');
        $submit->setLabel('Change');
        $this->add($submit);

        $inputFilterNew = new \Laminas\InputFilter\InputFilter();
        $inputFilterNew->add(
            array(
                'name' => 'name',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255)
                    ),
                    $this->createBlacklistValidator(),
                ),
            )
        );
        $inputFilterNew->add(
            array(
                'name' => 'subkeys',
                'continue_if_empty' => true, // Have empty value processed by callback validator
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateEmptySubkeys'),
                            'message' => "Value is required and can't be empty", // default notEmpty message
                        ),
                    ),
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255)
                    ),
                ),
            )
        );
        $inputFilterNew->add(
            array(
                'name' => 'value',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255)
                    ),
                ),
            )
        );
        $inputFilter->add($inputFilterNew, 'new_value');
        $this->setInputFilter($inputFilter);
    }

    /**
     * Validator callback for subkeys input
     *
     * @internal
     * @param string $value
     * @param array $context
     * @return bool TRUE if 'name' is empty or 'name' and 'subkeys' are not empty
     */
    public function validateEmptySubkeys($value, $context)
    {
        $name = \Laminas\Filter\StaticFilter::execute($context['name'], 'StringTrim');
        if ($name != '' and $value == '') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Create a validator that forbids any existing name except the given one
     *
     * @param string $name Existing name to allow (default: none)
     * @return \Library\Validator\NotInArray Validator object
     **/
    protected function createBlacklistValidator($name = null)
    {
        $blacklist = array();
        foreach ($this->_definedValues as $value) {
            if ($name != $value['Name']) {
                $blacklist[] = $value['Name'];
            }
        }
        return new \Library\Validator\NotInArray(
            array(
                'haystack' => $blacklist,
                'caseSensitivity' => \Library\Validator\NotInArray::CASE_INSENSITIVE
            )
        );
    }

    /**
     * Add and rename values and set 'InspectRegistry' option according to form data
     *
     * Form elements will not be updated.
     **/
    public function process()
    {
        $data = $this->getData();
        $registryManager = $this->getOption('registryManager');
        $name = $data['new_value']['name'];
        if ($name) {
            $registryManager->addValueDefinition(
                $name,
                $data['new_value']['root_key'],
                $data['new_value']['subkeys'],
                $data['new_value']['value']
            );
        }
        foreach ($this->getDefinedValues() as $value) {
            $registryManager->renameValueDefinition(
                $value['Name'],
                $data['existing'][base64_encode($value['Name'])]
            );
        }
    }
}
