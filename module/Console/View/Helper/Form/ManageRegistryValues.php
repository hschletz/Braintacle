<?php

/**
 * ManageRegistryValues form renderer
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

use Laminas\Form\Fieldset;
use Laminas\Form\FormInterface;

/**
 * ManageRegistryValues form renderer
 */
class ManageRegistryValues extends Form
{
    public function renderContent(FormInterface $form): string
    {
        $view = $this->getView();
        $fieldsetHelper = $view->plugin('consoleFormFieldset');
        $output = '';
        foreach ($form as $element) {
            if ($element instanceof Fieldset) {
                $name = $element->getName();
                if ($name == 'existing' and count($element)) {
                    $fieldset = '';
                    foreach ($element as $subElement) {
                        $row = $view->formElement($subElement);
                        $row .= $view->htmlElement('span', $view->escapeHtml($subElement->getLabel()));
                        $row .= $view->htmlElement(
                            'a',
                            $view->translate('Delete'),
                            array(
                                'href' => $view->consoleUrl(
                                    'preferences',
                                    'deleteregistryvalue',
                                    array(
                                        'name' => base64_decode(
                                            str_replace(
                                                array('existing[', ']'),
                                                '',
                                                $subElement->getName()
                                            )
                                        )
                                    )
                                )
                            )
                        );
                        $fieldset .= $view->htmlElement('label', $row);
                        $fieldset .= $view->formElementErrors($subElement);
                    }
                    $output .= $fieldsetHelper->renderFieldsetElement($element, $fieldset);
                } elseif ($name == 'new_value') {
                    $output .= $fieldsetHelper->render($element);
                }
            } else {
                $output .= $view->formRow($element);
            }
        }
        return $output;
    }
}
