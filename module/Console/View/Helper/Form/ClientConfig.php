<?php
/**
 * Client config form renderer
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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
 * Client config form renderer
 */
class ClientConfig extends AbstractHelper
{
    /** {@inheritdoc} */
    public function renderElements(\Zend\Form\FormInterface $form)
    {
        $output = '';
        foreach ($form as $element) {
            if ($element instanceof \Zend\Form\Fieldset) {
                $output .= $this->renderFieldset($element, $form->getClientObject());
            } else {
                $output .= $this->getView()->formRow($element);
            }
        }
        return $output;
    }

    /**
     * Render a single fieldset
     *
     * @param \Zend\Form\Fieldset $fieldset
     * @param \Model\ClientOrGroup $object
     */
    public function renderFieldset(\Zend\Form\Fieldset $fieldset, \Model\ClientOrGroup $object)
    {
        $view = $this->getView();
        $default = $view->translate('Default');
        $effective = $view->translate('Effective');
        $yes = $view->translate('Yes');
        $no = $view->translate('No');

        $output = '';
        foreach ($fieldset as $element) {
            if ($element->getAttribute('disabled')) {
                continue;
            }
            // FormRow helper would generate content in an unsuitable structure.
            // Generate components individually.
            $output .= $view->formLabel($element);
            $output .= '<span>';
            $output .= $view->formElement($element);

            preg_match('/.*\[(.*)\]$/', $element->getName(), $matches);
            $option = $matches[1];
            if ($option != 'scanThisNetwork') {
                $defaultValue = $object->getDefaultConfig($option);
                if ($element instanceof \Zend\Form\Element\Checkbox) {
                    $defaultValue = $defaultValue ? $yes : $no;
                }
                $info = sprintf('%s: %s', $default, $defaultValue);
                if ($object instanceof \Model\Client\Client) {
                    $effectiveValue = $object->getEffectiveConfig($option);
                    if ($element instanceof \Zend\Form\Element\Checkbox) {
                        $effectiveValue = $effectiveValue ? $yes : $no;
                    }
                    $info .= sprintf(', %s: %s', $effective, $effectiveValue);
                }
                $output .= $view->escapeHtml("($info)");
            }

            $output .= '</span>';
            $output .= $view->formElementErrors($element);
        }

        return $view->plugin('consoleFormFieldset')->renderFieldsetElement($fieldset, $output);
    }
}
