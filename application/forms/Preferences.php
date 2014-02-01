<?php
/**
 * Base class for display/setting of preferences
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
 * Base class for display/setting of preferences
 *
 * This class provides automatic generation of form elements depending on
 * datatype and all necessary interaction with the application's configuration
 * via {@link loadDefaults()} and {@link process()}.
 *
 * Subclasses need to populate {@link $_types} and {@link $_labels} before this
 * implementation of {@link init()} is invoked.
 * @package Forms
 */
abstract class Form_Preferences extends Form_Normalized
{

    /**
     * Field name => datatype pairs
     *
     * The data in this array is used to automatically create form elements. The
     * key is the name of a valid option defined by {@link Model_Config} and
     * will also be used as the name of the element. The value is the datatype
     * of the option. It determines the element type: 'bool' creates a checkbox
     * etc. It also affects processing of the value: integers will be localized/
     * normalized etc. If the value is an array, a Zend_Form_Element_Select will
     * be generated with the array data as content.
     * @var array
     */
    protected $_types;

    /**
     * Field name => label pairs
     *
     * Labels used for the elements. The subclass is responsible for translation.
     * @var array
     */
    protected $_labels;

    /**
     * Field name => good value pairs
     *
     * If a good value is defined for a field, a warning will be given if a
     * field is set to a different value. Good values can be defined for
     * deprecated or discouraged settings to remind the user to disable them.
     * @var array
     */
    protected $_goodValues = array();

    /**
     * Indicator for the presence of bad values
     * @var bool
     */
    protected $_hasBadValues;

    /**
     * Initialize form, generate elements dynamically
     */
    public function init()
    {
        $this->setMethod('POST');

        // Create elements dynamically
        foreach ($this->_types as $name => $type) {
            // Create element based on datatype
            switch ($type) {
                case 'bool':
                    $element = new Zend_Form_Element_Checkbox($name);
                    break;
                case 'clob':
                    $element = new Zend_Form_Element_Textarea($name);
                    break;
                case 'integer':
                    $element = new Zend_Form_Element_Text($name);
                    $element->addValidator('Int', false, array('options' =>'locale'));
                    break;
                case 'text':
                    $element = new Zend_Form_Element_Text($name);
                    break;
                default:
                    if (is_array($type)) {
                        $element = new Zend_Form_Element_Select($name);
                        $element->setDisableTranslator(true); // Content assumed to be already translated
                        $element->setMultiOptions($type);
                    } else {
                        throw new UnexpectedValueException('Unknown element type for ' . $name);
                    }
            }

            // Add label, assumed to be already translated
            $element->setDisableTranslator(true);
            $element->setLabel($this->_labels[$name]);

            $this->addElement($element);
        }

        // Add submit button
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Set');
        $this->addElement($submit);
    }

    /**
     * Get the datatype for an element
     */
    public function getType($name)
    {
        return $this->_types[$name];
    }

    /**
     * Set form values from current option values
     */
    public function loadDefaults()
    {
        foreach ($this->_types as $name => $type) {
            $this->setDefault($name, Model_Config::get($name));
        }
        $this->_markBadValues();
    }

    /**
     * Set option values from form data
     *
     * This method handles validation. It is not necessary to use isValid()
     * first. Invalid data will be handled as usual, but the presence of invalid
     * fields will not block processing of valid fields.
     * @param array $data submitted form data
     */
    public function process($data)
    {
        // Iterate over raw data instead of getValues() because normalization
        // would fail on invalid data.
        foreach ($data as $name => $value) {
            if ($name == 'submit') {
                continue; // Skip 'submit' button
            }
            $element = $this->getElement($name);
            if (!$element) {
                continue; // Skip unknown elements
            }
            if ($element->isValid($value)) { // Process only valid fields
                if ($this->_types[$name] == 'bool') {
                    // Convert checkbox values to real boolean to make the next
                    // step work with option values of NULL
                    $value = $element->isChecked();
                } else {
                    // Now that the value is known to be valid, it can be normalized.
                    $value = $this->getValue($name);
                    $this->setDefault($name, $value);
                }
                // Set new value only if it is different from the previous one.
                // This keeps the database rid of the application defaults,
                // allowing future changes of defaults to take effect unless
                // manually overridden.
                if ($value != Model_Config::get($name)) {
                    // Convert booleans back to string representation
                    // (implicit cast would yield '' instead of '0')
                    if ($this->_types[$name] == 'bool') {
                        $value = $value ? '1' : '0';
                    }
                    Model_Config::set($name, $value);
                }
            }
        }
        $this->_markBadValues();
    }

    /**
     * Render form
     * @param Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view = null)
    {
        // Prepend warning about bad values before rendering
        $output = '';
        if ($this->_hasBadValues) {
            // Can't use htmlTag helper here because $view is NULL.
            $output = '<p class="textcenter red">';
            $output .= Zend_Registry::get('Zend_Translate')->_(
                'Some settings are discouraged and should be changed.'
            );
            $output .= "<p>\n";
        }
        $output .= parent::render($view);
        return $output;
    }

    /**
     * Set 'badValue' class for elements with bad values
     */
    protected function _markBadValues()
    {
        foreach ($this->_goodValues as $name => $value) {
            if ($this->getValue($name) != $value) { // Use $this->getValue() for normalization.
                $this->getElement($name)->setAttrib('class', 'badValue');
                $this->_hasBadValues = true;
            }
        }
    }

}
