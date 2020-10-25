<?php
/**
 * Render all elements of a fieldset/form
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

/**
 * Render all elements of a fieldset/form
 *
 * Similar to \Zend\Form\View\Helper\FormCollection, with workarounds for some
 * ZF/browser bugs:
 *
 * - A fieldset's "name" attribute can be used:
 *   https://github.com/zendframework/zend-form/issues/86
 * - Fieldset content is wrapped in a div to allow styling as grid in
 *   chromium-based browsers: https://bugs.chromium.org/p/chromium/issues/detail?id=375693
 *
 * Regular fieldsets (i.e. not forms) are wrapped in a fieldset element.
 */

class Fieldset extends \Zend\Form\View\Helper\AbstractHelper
{
    /**
     * Render fieldsets and form elements
     *
     * @param \Zend\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function __invoke(\Zend\Form\FieldsetInterface $fieldset)
    {
        return $this->render($fieldset);
    }

    /**
     * Render fieldset content and containing markup
     *
     * @param \Zend\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function render(\Zend\Form\FieldsetInterface $fieldset)
    {
        $markup = $this->renderElements($fieldset);
        if (!$fieldset instanceof \Zend\Form\FormInterface) {
            $markup = $this->renderFieldsetElement($fieldset, $markup);
        }
        return $markup;
    }

    /**
     * Render fieldset element with label and form elements
     *
     * @param \Zend\Form\FieldsetInterface $fieldset
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
     * @param \Zend\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function renderLabel(\Zend\Form\FieldsetInterface $fieldset)
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
     * @param \Zend\Form\FieldsetInterface $fieldset
     * @return string
     */
    public function renderElements(\Zend\Form\FieldsetInterface $fieldset)
    {
        $markup = '';
        foreach ($fieldset as $element) {
            if ($element instanceof \Zend\Form\FieldsetInterface) {
                $markup .= $this->render($element);
            } else {
                $markup .= $this->getView()->formRow($element);
            }
        }
        return $markup;
    }
}
