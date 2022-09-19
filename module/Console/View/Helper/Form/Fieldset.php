<?php

/**
 * Render all elements of a fieldset/form
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

namespace Console\View\Helper\Form;

use Laminas\Form\FieldsetInterface;
use Laminas\Form\FormInterface;
use Laminas\Form\View\Helper\FormRow;

/**
 * Render all elements of a fieldset/form
 *
 * Similar to \Laminas\Form\View\Helper\FormCollection, with workarounds for
 * some Laminas/browser bugs:
 *
 * - A fieldset's "name" attribute can be used:
 *   https://github.com/laminas/laminas-form/issues/42
 * - Fieldset content is wrapped in a div to allow styling as grid in
 *   chromium-based browsers:
 *   https://bugs.chromium.org/p/chromium/issues/detail?id=375693
 *
 * Regular fieldsets (i.e. not forms) are wrapped in a fieldset element.
 */

class Fieldset extends \Laminas\Form\View\Helper\AbstractHelper
{
    /**
     * Render fieldsets and form elements
     *
     * @param \Laminas\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function __invoke(FieldsetInterface $fieldset, $labelPosition = FormRow::LABEL_PREPEND)
    {
        return $this->render($fieldset, $labelPosition);
    }

    /**
     * Render fieldset content and containing markup
     *
     * @param \Laminas\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function render(FieldsetInterface $fieldset, $labelPosition = FormRow::LABEL_PREPEND)
    {
        $markup = $this->renderElements($fieldset, $labelPosition);
        if (!$fieldset instanceof FormInterface) {
            $markup = $this->renderFieldsetElement($fieldset, $markup);
        }
        return $markup;
    }

    /**
     * Render fieldset element with label and form elements
     *
     * @param \Laminas\Form\FieldsetInterface $fieldset
     * @param string $content Fieldset content without label. If omitted, content gets generated from fieldset elements.
     * @return string
     */
    public function renderFieldsetElement($fieldset, $content = null)
    {
        if ($content === null) {
            $content = $this->renderElements($fieldset);
        }
        $markup = $this->renderLabel($fieldset) . '<div>' . $content . '</div>';
        return $this->getView()->htmlElement('fieldset', $markup, $fieldset->getAttributes());
    }

    /**
     * Render label (legend element). Label gets translated and escaped.
     *
     * @param \Laminas\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function renderLabel(\Laminas\Form\FieldsetInterface $fieldset)
    {
        $label = $fieldset->getLabel();
        if ($label) {
            $label = $this->getView()->translate($label);
            $label = $this->getView()->escapeHtml($label);
            $label = "<legend>$label</legend>";
        }
        return $label;
    }

    /**
     * Render elements
     *
     * @param \Laminas\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function renderElements(FieldsetInterface $fieldset, $labelPosition = FormRow::LABEL_PREPEND)
    {
        $view = $this->getView();
        $view->plugin('formElementErrors')->setAttributes(['class' => 'errors']);

        $markup = '';
        foreach ($fieldset as $element) {
            if ($element instanceof FieldsetInterface) {
                $markup .= $this->render($element);
            } else {
                $markup .= $view->plugin('formRow')($element, $labelPosition);
            }
        }
        return $markup;
    }
}
