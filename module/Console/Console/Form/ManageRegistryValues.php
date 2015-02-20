<?php
/**
 * Form for defining and deleting inventoried registry values
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
 * Form for defining and deleting inventoried registry values
 *
 * The form requires the following options to be set:
 *
 * - **config:** \Model\Config instance, required by init().
 * - **registryManager:** \Model\Registry\RegistryManager instance, required by
 *   init() and process()
 *
 * The factory injects these automatically.
 */
class ManageRegistryValues extends Form
{
    /**
     * Array of all values defined in the database
     * @var \Model_RegistryValue[]
     **/
    protected $_definedValues = array();

    /** {@inheritdoc} */
    public function init()
    {
        $inputFilter = new \Zend\InputFilter\InputFilter;

        // Create list of values as array because nested iteration does not work with ResultSet objects.
        $this->_definedValues = iterator_to_array($this->getOption('registryManager')->getValueDefinitions());

        // Subform for enabling/disabling registry inspection, in addition to
        // the same setting in preferences.
        $fieldsetInspect = new \Zend\Form\Fieldset('inspect');
        $inspect = new Element\Checkbox('inspect');
        $inspect->setLabel('Inspect registry')
                ->setChecked($this->getOption('config')->inspectRegistry);
        $fieldsetInspect->add($inspect);
        $this->add($fieldsetInspect);

        // Subform for existing values
        $fieldsetExisting = new \Zend\Form\Fieldset('existing');
        $inputFilterExisting = new \Zend\InputFilter\InputFilter;
        // Create text elements for existing values to rename them
        foreach ($this->_definedValues as $value) {
            $name = $value['Name'];
            $elementName = "value_$value[Id]_name";
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
                        $this->_createBlacklistValidator($name),
                    ),
                )
            );
            $fieldsetExisting->add($element);
        }
        $this->add($fieldsetExisting);
        $inputFilter->add($inputFilterExisting, 'existing');

        // Subform for new value
        $fieldsetNew = new \Zend\Form\Fieldset('new_value');

        $newName = new Element\Text('name');
        $newName->setLabel('Name');
        $fieldsetNew->add($newName);

        $newRootKey = new Element\Select('root_key');
        $newRootKey->setLabel('Root key')
                   ->setAttribute('type', 'select_untranslated')
                   ->setValueOptions(\Model_RegistryValue::rootKeys())
                   ->setValue(\Model_RegistryValue::HKEY_LOCAL_MACHINE);
        $fieldsetNew->add($newRootKey);

        // Additional validation in isValid()
        $newSubKeys = new Element\Text('subkeys');
        $newSubKeys->setLabel('Subkeys');
        $fieldsetNew->add($newSubKeys);

        $newValue = new Element\Text('value');
        $newValue->setLabel('Only this value (optional)');
        $fieldsetNew->add($newValue);

        $submit = new \Library\Form\Element\Submit('submit');
        $submit->setLabel('Change');
        $fieldsetNew->add($submit);

        $this->add($fieldsetNew);

        $inputFilterNew = new \Zend\InputFilter\InputFilter;
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
                    $this->_createBlacklistValidator(),
                ),
            )
        );
        $subkeysValidator = new \Zend\Validator\Callback;
        $subkeysValidator->setCallback(array($this, 'validateEmptySubkeys'))
                         ->setTranslatorTextDomain('default')
                         ->setMessage(
                             "Value is required and can't be empty",
                             \Zend\Validator\Callback::INVALID_VALUE
                         );
        $inputFilterNew->add(
            array(
                'name' => 'subkeys',
                'continue_if_empty' => true, // Have empty value processed by callback validator
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    $subkeysValidator,
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
        $name = \Zend\Filter\StaticFilter::execute($context['name'], 'StringTrim');
        if ($name != '' and $value == '') {
            return false;
        } else {
            return true;
        }
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset=null)
    {
        $output = '';
        $name = $fieldset->getName();
        switch ($name) {
            case 'inspect':
                $output .= "<div class='textcenter'>\n";
                $output .= $view->formRow($fieldset->get('inspect'), 'append') . "\n";
                $output .= "</div>\n";
                break;
            case 'existing':
                $table = '';
                foreach ($this->_definedValues as $value) {
                    $id = $value['Id'];
                    $element = $fieldset->get("value_{$id}_name");
                    $row = $view->htmlTag(
                        'td',
                        $view->formElement($element) . $view->formElementErrors($element, array('class' => 'errors'))
                    );
                    $row .= $view->htmlTag(
                        'td',
                        $view->escapeHtml($element->getLabel())
                    );
                    $row .= $view->htmlTag(
                        'td',
                        $view->htmlTag(
                            'a',
                            $view->translate('Delete'),
                            array(
                                'href' => $view->consoleUrl(
                                    'preferences',
                                    'deleteregistryvalue',
                                    array('id' => $id,)
                                )
                            )
                        )
                    );
                    $table .= $view->htmlTag('tr', $row);
                }
                $output .= $view->htmlTag('table', $table);
                break;
            case 'new_value':
                $output .= parent::renderFieldset($view, $fieldset);
                break;
            default:
                if ($fieldset == $this) {
                    $output .= $this->renderFieldset($view, $fieldset->get('inspect'));
                    $output .= $view->htmlTag('h2', $view->translate('Values'));
                    $output .= $this->renderFieldset($view, $fieldset->get('existing'));
                    $output .= $view->htmlTag('h2', $view->translate('Add'));
                    $output .= $this->renderFieldset($view, $fieldset->get('new_value'));
                }
                break;
        }
        return $output;
    }

    /**
     * Create a validator that forbids any existing name except the given one
     *
     * @param string $name Existing name to allow (default: none)
     * @return \Library\Validator\NotInArray Validator object
     **/
    protected function _createBlacklistValidator($name=null)
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

        $this->getOption('config')->inspectRegistry = $data['inspect']['inspect'];

        $name = $data['new_value']['name'];
        if ($name) {
            $this->getOption('registryManager')->addValueDefinition(
                $name,
                $data['new_value']['root_key'],
                $data['new_value']['subkeys'],
                $data['new_value']['value']
            );
        }
        foreach ($this->_definedValues as $value) {
            $value->rename(
                $data['existing']["value_$value[Id]_name"]
            );
        }
    }
}
