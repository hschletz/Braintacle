<?php

/**
 * Define/delete custom fields
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

/**
 * Define/delete custom fields
 *
 * The "CustomFieldManager" option is required by init() and process(). The
 * factory automatically injects a \Model\Client\CustomFieldManager instance.
 */
class DefineFields extends Form
{
    /**
     * Array of name => datatype pairs (translated)
     * @var string[]
     **/
    protected $_definedFields = array();

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $translatedTypes = array(
            'text' => $this->_('Text'),
            'clob' => $this->_('Long text'),
            'integer' => $this->_('Integer'),
            'float' => $this->_('Float'),
            'date' => $this->_('Date'),
        );

        $fields = new \Laminas\Form\Fieldset('Fields');
        $this->add($fields);
        $inputFilterFields = new \Laminas\InputFilter\InputFilter();

        foreach ($this->getOption('CustomFieldManager')->getFields() as $name => $type) {
            if ($name == 'TAG') { // Static field, can not be edited
                continue;
            }
            $this->_definedFields[$name] = $translatedTypes[$type];
            $element = new \Laminas\Form\Element\Text($name);
            $element->setValue($name);
            $fields->add($element);

            $filter = array(
                'name' => $name,
                'required' => true,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('min' => 1, 'max' => 255),
                    ),
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateName'),
                            'callbackOptions' => $name,
                            'message' => $this->_('The name already exists'),
                        ),
                    ),
                ),
            );
            $inputFilterFields->add($filter);
        }

        // Empty text field to create new field.
        $newName = new \Laminas\Form\Element\Text('NewName');
        $newName->setLabel('Add');
        $this->add($newName);

        // Datatype of new field
        $newType = new \Laminas\Form\Element\Select('NewType');
        $newType->setValueOptions($translatedTypes);
        $this->add($newType);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Change');
        $this->add($submit);

        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add($inputFilterFields, 'Fields');
        $inputFilter->add(
            array(
                'name' => 'NewName',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255),
                    ),
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateName'),
                            'message' => $this->_('The name already exists'),
                        ),
                    ),
                ),
            )
        );
        $this->setInputFilter($inputFilter);
    }

    /**
     * Validator callback for field names
     *
     * Prevents any duplicates among existing/changed/added field names.
     *
     * @param string $value
     * @param array $context
     * @param string $originalValue Current field name to be changed (NULL for adding field)
     * @return bool
     * @internal
     */
    public function validateName($value, $context, $originalValue = null)
    {
        if ($originalValue) {
            if ($value == $originalValue) {
                // Unchanged, always valid. Eventual duplicate will be reported
                // for the other value.
                return true;
            } else {
                $blacklist = $context;
                // Allow new value for current field. If a new value is assigned
                // twice, the dupicate will still be present in the blacklist.
                unset($blacklist[$originalValue]);
            }
        } else {
            $blacklist = $context['Fields'];
        }
        // Merge keys (original names) and values (new names).
        $blacklist = array_merge($blacklist, array_keys($blacklist));

        // Search $blacklist for $value, case insensitive, ignoring leading/
        // trailing whitespace. Succeed only if no match is found.
        return !preg_grep('/^\s*' . preg_quote($value, '/') . '\s*$/ui', $blacklist);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Laminas\View\Renderer\PhpRenderer $view, \Laminas\Form\Fieldset $fieldset = null)
    {
        $output = "<div class='table'>\n";
        $fields = $this->get('Fields');
        foreach ($this->_definedFields as $name => $type) {
            $element = $fields->get($name);
            if ($element->getMessages()) {
                $element->setAttribute('class', 'input-error');
            }
            $row = $view->formText($element) . "\n";
            $row .= $view->htmlElement('span', $view->translate($type), array('class' => 'cell'));
            $row .= $view->htmlElement(
                'span',
                $view->htmlElement(
                    'a',
                    $view->translate('Delete'),
                    array(
                        'href' => $view->consoleUrl(
                            'preferences',
                            'deletefield',
                            array('name' => $name)
                        )
                    ),
                    true
                ),
                array('class' => 'cell')
            );
            $output .= $view->htmlElement('div', $row, array('class' => 'row'));
            $output .= $view->formElementErrors($element, array('class' => 'error'));
            $output .= "\n";
        }

        $newName = $this->get('NewName');
        $output .= $view->htmlElement(
            'div',
            $view->formRow($newName, null, false) . $view->formRow($this->get('NewType')),
            array('class' => 'row')
        );
        $output .= $view->formElementErrors($newName, array('class' => 'error'));
        $output .= $view->formRow($this->get('Submit'));
        $output .= "\n</div>\n";
        return $output;
    }

    /**
     * Add and rename fields according to form data
     *
     * Form elements are not updated. The form instance is invalid after calling
     * process() and should no longer be used.
     **/
    public function process()
    {
        $customFieldManager = $this->getOption('CustomFieldManager');
        $data = $this->getData();
        if ($data['NewName']) {
            $customFieldManager->addField($data['NewName'], $data['NewType']);
        }
        foreach ($data['Fields'] as $oldName => $newName) {
            $customFieldManager->renameField($oldName, $newName);
        }
    }
}
