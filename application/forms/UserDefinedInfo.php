<?php
/**
 * Form for display/setting of user defined fields for a computer.
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * Form for display/setting of user defined fields for a computer.
 *
 * The field names are automatically retrieved from the database. Integer,
 * float and date values are formatted with the default locale upon display and
 * must be entered localized. The methods for interaction with the application
 * (setDefault[s](), getValue[s]()) however accept/return only canonicalized
 * values (standard integers/floats and ISO-style date strings).
 * @package Forms
 */
class Form_UserDefinedInfo extends Zend_Form
{

    /**
     * Field name => datatype pairs
     @var array
     */
    protected $_types;

    /**
     * Retrieve field name and types from the database and create form elements.
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        $this->setMethod('post');

        // Category ('tag') always comes first
        $category = new Zend_Form_Element_Text('tag');
        $category->addFilter('StringTrim')
                 ->addValidator('StringLength', false, array(0, 255))
                 ->setLabel('Category');
        $this->addElement($category);

        $this->_types = Model_UserDefinedInfo::getTypes();
        foreach ($this->_types as $name => $type) {
            if ($name == 'tag') {
                continue; // element already created
            }
            $element = new Zend_Form_Element_Text($name);
            $element->setDisableTranslator(true) // Don't translate user-defined names
                    ->setLabel(ucfirst($name))
                    ->addFilter('StringTrim');
            switch ($type) {
                case 'text':
                    $element->addValidator('StringLength', false, array(0, 255));
                    break;
                case 'integer':
                    $element->addValidator('Int', false, array('options' =>'locale'));
                    break;
                case 'float':
                    $element->addValidator('Float', false, array('options' =>'locale'));
                    break;
                case 'date':
                    $element->addValidator('Date', false, array('options' =>'locale'));
                    break;
            }
            $this->addElement($element);
        }

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Change');
        $this->addElement($submit);
    }

    /**
     * Convert non-text values to their localized form
     */
    public function setDefault($name, $value)
    {
        switch ($this->_types[$name]) {
            case 'integer':
            case 'float':
                $value = Zend_Locale_Format::toNumber($value);
                break;
            case 'date':
                if (!is_null($value)) {
                    $value = $this->getView()->date($value, Zend_Date::DATE_MEDIUM);
                }
                break;
        }
        return parent::setDefault($name, $value);
    }

    /**
     * Run {@link _normalize()} on retrieved value.
     */
    public function getValue($name)
    {
        return $this->_normalize($name, parent::getValue($name));
    }

    /**
     * Run {@link _normalize()} on retrieved values.
     */
    public function getValues($suppressArrayNotation=false)
    {
        $values = parent::getValues($suppressArrayNotation);

        foreach ($values as $name => $value) {
            $values[$name] = $this->_normalize($name, $value);
        }

        return $values;
    }

    /**
     * Normalize a retrieved value.
     * - Non-text values are converted into a non-localized form
     * - Empty strings are converted to NULL. This makes the values suitable
     *   for direct insertion into the database.
     * @param string $name Field name, needed to determine datatype
     * @param string $value Raw value
     * @return mixed Normalized value
     */
    protected function _normalize($name, $value)
    {
        if ($value === '') {
            $value = null;
        } else {
            switch ($this->_types[$name]) {
                case 'integer':
                case 'float':
                    $value = Zend_Locale_Format::getNumber($value);
                    break;
                case 'date':
                        $date = new Zend_Date($value);
                        $value = $date->toString('yyyy-MM-dd');
                    break;
            }
        }
        return $value;
    }

}
