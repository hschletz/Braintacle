<?php
/**
 * Base class for forms
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
 */

namespace Console\Form;

/**
 * Base class for forms
 *
 * This base class extends \Zend\Form\Form with some convenience functionality:
 *
 * - The constructor sets the "class" attribute to "form" and a second value
 *   derived from the class name: Console\Form\Foo\Bar becomes form_foo_bar and
 *   so on. This allows general and individual styling of form content.
 *
 * - The form's "id" attribute is set to the class-derived value ("form_foo_bar"
 *   in the above example). This can be overridden manually if necessary.
 *
 * - Automatic CSRF protection via hidden "_csrf" element.
 *
 * - Default rendering methods.
 *
 * - Helper methods for dealing with localized integer, float and date formats.
 */
class Form extends \Zend\Form\Form
{
    /** {@inheritdoc} */
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);

        $class = get_class($this);
        $class = strtr($class, '\\', '_');
        $class = substr($class, strpos($class, '_') + 1);
        $class = strtolower($class);
        $this->setAttribute('class', 'form ' . $class);
        $this->setAttribute('id', $class);

        $csrf = new \Zend\Form\Element\Csrf('_csrf');
        $csrf->setCsrfValidatorOptions(array('timeout' => null)); // Rely on session cleanup
        $this->add($csrf);
    }

    /**
     * Render the form
     *
     * @param \Zend\View\Renderer\PhpRenderer $view
     * @return string HTML form code
     */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $this->prepare();
        $output  = $view->form()->openTag($this);
        $output .= "\n";
        if ($this->has('_csrf')) {
            $output .= "<div>";
            $output .= $view->formHidden($this->get('_csrf'));
            $output .= "</div>\n";
        }
        $output .= $this->renderFieldset($view, $this);
        $output .= "\n";
        $output .= $view->form()->closeTag();
        $output .= "\n";
        return $output;
    }

    /**
     * Render all elements from a fieldset
     *
     * This method iterates over all elements from the given fieldset and
     * renders them in a way appropriate for each element type. Subclasses with
     * more specialized rendering may extend or replace this method.
     *
     * @param \Zend\View\Renderer\PhpRenderer $view
     * @param \Zend\Form\Fieldset $fieldset
     * @return string HTML code
     */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset)
    {
        $output = "<div class='table'>\n";
        foreach ($fieldset as $element) {
            if ($element instanceof \Zend\Form\Element\Submit) {
                $output .= "<span class='cell'></span>\n";
                $output .= $view->formSubmit($element) . "\n";
            } elseif (!$element instanceof \Zend\Form\Element\Csrf) {
                $output .= $view->formRow($element, 'prepend', false) . "\n";
                if ($element->getMessages()) {
                    $output .= "<span class='cell'></span>\n";
                    $output .= $view->formElementErrors($element, array('class' => 'errors')) . "\n";
                }
            }
        }
        $output .= "</div>\n";
        return $output;
    }

    /**
     * Convert normalized integer, float or date values to localized string representation
     *
     * Subclasses can support localized input formats by overriding setData()
     * where this method can be used to preprocess specific fields. It accepts
     * strictly normalized input data:
     *
     * - Integers must contain only digits.
     * - Floats must contain only digits and at most 1 dot, but not at the end
     *   of the string.
     * - Dates must be passed as \Zend_Date objects or ISO date strings.
     *
     * Invalid input data is returned unmodified. The attached input filter
     * should take care of it.
     *
     * @param string $type Data type (integer, float, date). Any other value will be ignored.
     * @param mixed $value Normalized input value
     * @return mixed Localized or unmodified value
     */
    public function localize($type, $value)
    {
        switch ($type) {
            case 'integer':
                if (ctype_digit((string) $value)) {
                    $value = \Zend\Filter\StaticFilter::execute(
                        (integer) $value,
                        'NumberFormat',
                        array('type' => \NumberFormatter::TYPE_INT32)
                    );
                }
                break;
            case 'float':
                if ($value !== '' and preg_match('/^([0-9]+)?(\.[0-9]+)?$/', $value)) {
                    $numberFormat = new \Zend\I18n\Filter\NumberFormat;
                    $numberFormat->getFormatter()->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 100);
                    $value = $numberFormat->filter((float) $value);
                }
                break;
            case 'date':
                if ($value instanceof \Zend_Date) {
                    $value = $value->get(\Zend_Date::DATE_MEDIUM);
                } elseif (\Zend\Validator\StaticValidator::execute($value, 'Date')) {
                    $value = new \Zend_Date($value);
                    $value = $value->get(\Zend_Date::DATE_MEDIUM);
                }
                break;
        }
        return $value;
    }

    /**
     * Convert localized string representations to integer, float or date values
     *
     * Subclasses can support localized input formats by calling this method
     * from a filter.
     *
     * Non-string values get trimmed and converted to integer, float or
     * \Zend_Date, depending on $type. Invalid values are returned as string.
     * The input filter should validate filtered data by checking the datatype
     * via validateType().
     *
     * @param string $type Data type (integer, float, date). Any other value will be ignored.
     * @param string $value Localized input string
     * @return mixed Normalized value or input string
     */
    public function normalize($type, $value)
    {
        // Integers and floats are validated first to prevent successful parsing
        // of strings containing invalid characters with the invalid part simply
        // cut off.
        switch ($type) {
            case 'integer':
                $value = trim($value);
                if (\Zend\Validator\StaticValidator::execute($value, 'Zend\I18n\Validator\Int')) {
                    $value = \Zend\Filter\StaticFilter::execute(
                        $value,
                        'Zend\I18n\Filter\NumberParse',
                        array('type' => \NumberFormatter::TYPE_INT32)
                    );
                }
                break;
            case 'float':
                $value = trim($value);
                if (\Zend\Validator\StaticValidator::execute($value, 'Zend\I18n\Validator\Float')) {
                    $value = \Zend\Filter\StaticFilter::execute(
                        $value,
                        'Zend\I18n\Filter\NumberParse',
                        array('type' => \NumberFormatter::TYPE_DOUBLE)
                    );
                }
                break;
            case 'date':
                $value = trim($value);
                $validator = new \Zend\I18n\Validator\DateTime;
                $validator->setDateType(\IntlDateFormatter::SHORT);
                if ($validator->isValid($value)) {
                    $value = new \Zend_Date($value);
                }
                break;
        }
        return $value;
    }

    /**
     * Validate datatype
     *
     * This method can be used to validate data returned by normalize(). It
     * checks the value's actual datatype (integer, float, \Zend_Date) against
     * the expected type.
     *
     * @param string $type Expected type (integer, float, date). Any other value will always yield TRUE.
     * @param mixed $value Value to test
     * @return bool
     */
    public function validateType($type, $value)
    {
        switch ($type) {
            case 'integer':
                $valid = is_int($value);
                break;
            case 'float':
                $valid = is_float($value);
                break;
            case 'date':
                $valid = $value instanceof \Zend_Date;
                break;
            default:
                $valid = true;
        }
        return $valid;
    }
}
