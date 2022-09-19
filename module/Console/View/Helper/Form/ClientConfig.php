<?php

/**
 * Client config form renderer
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

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Fieldset;
use Laminas\Form\FormInterface;
use Model\Client\Client;
use Model\ClientOrGroup;

/**
 * Client config form renderer
 */
class ClientConfig extends Form
{
    public function render(\Laminas\Form\FormInterface $form): string
    {
        $this->getView()->consoleScript('form_clientconfig.js');

        return $this->renderForm($form);
    }

    public function renderContent(FormInterface $form): string
    {
        $output = '';
        foreach ($form as $element) {
            if ($element instanceof Fieldset) {
                $output .= $this->renderFieldset($element, $form->getClientObject());
            } else {
                $output .= $this->getView()->formRow($element);
            }
        }
        return $output;
    }

    /**
     * Render a single fieldset.
     */
    public function renderFieldset(Fieldset $fieldset, ClientOrGroup $object): string
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
                if ($element instanceof Checkbox) {
                    $defaultValue = $defaultValue ? $yes : $no;
                }
                $info = sprintf('%s: %s', $default, $defaultValue);
                if ($object instanceof Client) {
                    $effectiveValue = $object->getEffectiveConfig($option);
                    if ($element instanceof Checkbox) {
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
