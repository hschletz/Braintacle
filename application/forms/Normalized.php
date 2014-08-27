<?php
/**
 * Base class for forms that process localized data
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
 * Base class for forms that process localized data
 *
 * This base class provides localization and normalization of numbers and dates.
 * The application interface is normalized while the user interface is
 * localized.
 *
 * The user enters integers, floats and dates in a localized form and
 * {@link getValue()}/{@link getValues()} return normalized, locale-independent
 * values (integer/float variables or Zend_Date objects) that can be processed
 * directly without further conversion. Additionally, empty strings are
 * converted to NULL. This rarely makes a difference in PHP, but a big
 * difference when the value gets written to a database.
 *
 * Similarly, {@link setDefault()} and
 * {@link setDefaults()} accept normalized data and localize it before applying
 * it to the form.
 *
 * This is an abstract class. Derived classes must implement {@link getType()}.
 * @package Forms
 */
abstract class Form_Normalized extends Zend_Form
{

    /**
     * Retrieve the datatype of an element
     *
     * The result can be any string, but only 'integer', 'float' and 'date' will
     * be recognized.
     * @param string Element name
     * @return string
     */
    abstract public function getType($name);

    /**
     * Localize a value
     * @param string $name Element name
     * @param $value mixed Element value
     * @return mixed Localized value
     */
    public function localize($name, $value)
    {
        switch ($this->getType($name)) {
            case 'integer':
            case 'float':
                $value = Zend_Locale_Format::toNumber($value);
                break;
            case 'date':
                if (!is_null($value)) {
                    $value = $this->getView()->date($value, Zend_Date::DATE_MEDIUM, 'yyyy-MM-dd');
                }
                break;
        }
        return $value;
    }

    /**
     * Normalize a retrieved value.
     * - Non-text values are converted into a non-localized form
     * - Empty strings are converted to NULL. This makes the values suitable
     *   for direct insertion into the database.
     * - Non-empty Date elements are converted to a Zend_Date object
     * @param string $name Field name, needed to determine datatype
     * @param string $value Raw value
     * @return mixed Normalized value
     */
    protected function normalize($name, $value)
    {
        if ($value === '') {
            $value = null;
        } else {
            switch ($this->getType($name)) {
                case 'integer':
                case 'float':
                    $value = Zend_Locale_Format::getNumber((string) $value);
                    break;
                case 'date':
                    $value = new Zend_Date($value);
                    break;
            }
        }
        return $value;
    }

    /**
     * Set default value for an element
     * @param string $name
     * @param mixed $value
     * @return Zend_Form
     */
    public function setDefault($name, $value)
    {
        return parent::setDefault($name, $this->localize($name, $value));
    }

    /**
     * Set default values for elements
     *
     * Sets values for all elements specified in the array of $defaults.
     * @param array $defaults
     * @return Zend_Form
     */
    public function setDefaults(array $defaults)
    {
        foreach ($defaults as $name => $value) {
            $defaults[$name] = $this->localize($name, $value);
        }
        return parent::setDefaults($defaults);
    }

    /**
     * Retrieve value for single element
     * @param string $name
     * @return mixed
     */
    public function getValue($name)
    {
        return $this->normalize($name, parent::getValue($name));
    }

    /**
     * Retrieve all form element values
     * @param bool $suppressArrayNotation
     * @return array
     */
    public function getValues($suppressArrayNotation=false)
    {
        $values = parent::getValues($suppressArrayNotation);

        foreach ($values as $name => $value) {
            $values[$name] = $this->normalize($name, $value);
        }

        return $values;
    }

}
