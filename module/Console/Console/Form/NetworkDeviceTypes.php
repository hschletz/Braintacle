<?php
/**
 * Define/delete network device types
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

/**
 * Define/delete network device types
 *
 * The "DeviceManager" option is required by init() and process(). The factory
 * automatically injects a \Model\Network\DeviceManager instance.
 */
class NetworkDeviceTypes extends Form
{
    /**
     * Array of all types defined in the database
     * @var array type => count pairs
     **/
    protected $_definedTypes = array();

    /** {@inheritdoc} */
    public function init()
    {
        $types = new \Zend\Form\Fieldset('Types');
        $this->add($types);
        $inputFilterTypes = new \Zend\InputFilter\InputFilter;

        $this->_definedTypes = $this->getOption('DeviceManager')->getTypeCounts();
        foreach ($this->_definedTypes as $name => $count) {
            $element = new \Zend\Form\Element\Text($name);
            $element->setValue($name);
            $types->add($element);

            $callback = new \Zend\Validator\Callback;
            $callback->setCallback(array($this, 'validateName'))
                     ->setCallbackOptions($name)
                     ->setTranslatorTextDomain('default')
                     ->setMessage('The name already exists');
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
                    $callback,
                ),
            );
            $inputFilterTypes->add($filter);
        }

        $add = new \Zend\Form\Element\Text('Add');
        $add->setLabel('Add');
        $this->add($add);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Change');
        $this->add($submit);

        $callback = new \Zend\Validator\Callback;
        $callback->setCallback(array($this, 'validateName'))
                 ->setTranslatorTextDomain('default')
                 ->setMessage('The name already exists');
        $inputFilter = new \Zend\InputFilter\InputFilter;
        $inputFilter->add($inputFilterTypes, 'Types');
        $inputFilter->add(
            array(
                'name' => 'Add',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255),
                    ),
                    $callback,
                ),
            )
        );
        $this->setInputFilter($inputFilter);
    }

    /**
     * Validator callback for type names
     *
     * Prevents any duplicates among existing/changed/added names.
     *
     * @param string $value
     * @param array $context
     * @param string $originalValue Current name to be changed (NULL for adding name)
     * @return bool
     * @internal
     */
    public function validateName($value, $context, $originalValue=null)
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
            $blacklist = $context['Types'];
        }
        // Merge keys (original names) and values (new names).
        $blacklist = array_merge($blacklist, array_keys($blacklist));

        // Search $blacklist for $value, case insensitive, ignoring leading/
        // trailing whitespace. Succeed only if no match is found.
        return !preg_grep('/^\s*' . preg_quote($value, '/') . '\s*$/ui', $blacklist);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset=null)
    {
        $output = "<div class='table'>\n";
        $types = $this->get('Types');
        foreach ($this->_definedTypes as $name => $count) {
            $element = $types->get($name);
            if ($element->getMessages()) {
                $element->setAttribute('class', 'input-error');
            }
            $row = $view->formText($element);
            if ($count == 0) {
                $row .= $view->htmlTag(
                    'a',
                    $view->translate('Delete'),
                    array(
                        'href' => $view->consoleUrl(
                            'preferences',
                            'deletedevicetype',
                            array('name' => $name)
                        )
                    ),
                    true
                );
            }
            $output .= $view->htmlTag('div', $row);
            $output .= $view->formElementErrors($element, array('class' => 'error'));
            $output .= "\n";
        }

        $add = $this->get('Add');
        $output .= $view->formRow($add, null, false);
        $output .= $view->formElementErrors($add, array('class' => 'error'));
        $output .= $view->formRow($this->get('Submit'));
        $output .= "\n</div>\n";
        return $output;
    }

    /**
     * Add and rename types according to form data
     *
     * Form elements are not updated. The form instance is invalid after calling
     * process() and should no longer be used.
     **/
    public function process()
    {
        $data = $this->getData();
        $deviceManager = $this->getOption('DeviceManager');
        if ($data['Add']) {
            $deviceManager->addType($data['Add']);
        }
        foreach ($data['Types'] as $old => $new) {
            if ($old != $new) {
                $deviceManager->renameType($old, $new);
            }
        }
    }
}
