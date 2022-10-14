<?php

/**
 * Display/set values of custom fields for a client
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
 * Display/set values of custom fields for a client
 *
 * The field names and types are retrieved from the \Model\Client\CustomFieldManager
 * object passed via the "customFieldManager" option which is required by init().
 * The factory sets this automatically.
 *
 * Integer, float and date values are formatted with the default locale upon
 * display and must be entered localized. The data exchange methods (setData(),
 * getData()) however accept/return only canonicalized values (standard
 * integers/floats and \DateTime objects).
 */
class CustomFields extends Form
{
    /**
     * Field name => datatype pairs
     * @var string[]
     */
    protected $_types;

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $this->_types = $this->getOption('customFieldManager')->getFields();

        $fields = new \Laminas\Form\Fieldset('Fields');
        $inputFilterField = new \Laminas\InputFilter\InputFilter();
        foreach ($this->_types as $name => $type) {
            if ($type == 'clob') {
                $element = new \Laminas\Form\Element\Textarea($name);
            } else {
                $element = new \Laminas\Form\Element\Text($name);
            }
            if ($name == 'TAG') {
                $element->setLabel('Category');
            } else {
                $element->setLabel($name);
            }
            $fields->add($element);

            $filter = array(
                'name' => $name,
                'required' => false,
                'filters' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'filterField'),
                            'callback_params' => $type,
                        ),
                    ),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateField'),
                            'callbackOptions' => $type,
                        ),
                    ),
                ),
            );
            /** @psalm-suppress InvalidArgument Unable to infer string type of $name */
            $inputFilterField->add($filter);
        }
        $this->add($fields);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Change');
        $this->add($submit);

        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add($inputFilterField, 'Fields');
        $this->setInputFilter($inputFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        foreach ($data['Fields'] as $name => &$content) {
            $content = $this->localize($content, $this->_types[$name]);
        }
        return parent::setData($data);
    }

    /**
     * Filter callback
     *
     * @internal
     * @return mixed trimmed and normalized input depending on field type
     */
    public function filterField(?string $value, string $type)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value == '') {
            $value = null;
        } else {
            $value = $this->normalize($value, $type);
        }
        return $value;
    }

    /**
     * Validator callback
     *
     * @internal
     * @param string $value
     * @param array $context
     * @param string $type Field datatype
     * @return bool TRUE if $value is valid for given type
     */
    public function validateField($value, $context, $type)
    {
        switch ($type) {
            case 'text':
                $result = (\Laminas\Stdlib\StringUtils::getWrapper('UTF-8')->strlen($value) <= 255);
                break;
            case 'integer':
            case 'float':
            case 'date':
                $result = $this->validateType($value, $context, $type);
                break;
            default:
                $result = true;
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Laminas\View\Renderer\PhpRenderer $view, \Laminas\Form\Fieldset $fieldset)
    {
        if ($fieldset->getName() == 'Fields') {
            // Labels (except "Category") are user defined and must not be translated.
            $fieldset->get('TAG')->setLabel($view->translate('Category'));
            $formRow = $view->plugin('FormRow');
            $translatorEnabled = $formRow->isTranslatorEnabled();
            $formRow->setTranslatorEnabled(false);
            $output = parent::renderFieldset($view, $fieldset);
            $formRow->setTranslatorEnabled($translatorEnabled);
        } else {
            $output = $this->renderFieldset($view, $this->get('Fields'));
            $output .= "<div class='table'>\n";
            $output .= "<span class='cell'></span>\n";
            $output .= $view->formSubmit($fieldset->get('Submit')) . "\n";
            $output .= "</div>\n";
        }
        return $output;
    }
}
