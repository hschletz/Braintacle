<?php

/**
 * Base class for display/setting of preferences
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

namespace Console\Form\Preferences;

/**
 * Base class for display/setting of preferences
 *
 * This class provides the 'Preferences' fieldset (to be populated by
 * subclasses) and a submit button.
 */
abstract class AbstractForm extends \Console\Form\Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $preferences = new \Laminas\Form\Fieldset('Preferences');
        $this->add($preferences);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Set');
        $this->add($submit);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Laminas\View\Renderer\PhpRenderer $view, \Laminas\Form\Fieldset $fieldset)
    {
        if ($fieldset->getName()) {
            return parent::renderFieldset($view, $fieldset);
        }

        // Reimplement form renderer to align submit button with elements from Preferences fieldset.
        $output = "<div class='table'>\n";
        foreach ($this->get('Preferences') as $element) {
            if ($element instanceof \Laminas\Form\Fieldset) {
                $output .= $view->htmlElement(
                    'span',
                    $view->translate($element->getLabel()),
                    array('class' => 'label'),
                    true
                ) . "\n";
                $output .= $view->htmlElement(
                    'fieldset',
                    "<legend></legend>\n" . $this->renderFieldset($view, $element)
                );
            } else {
                $output .= $view->formRow($element, 'prepend', false);
                if ($element->getMessages()) {
                    $output .= "\n<div class='row'>\n<span class='cell'></span>\n";
                    $output .= $view->formElementErrors($element, array('class' => 'errors'));
                    $output .= "\n</div>";
                }
            }
            $output .= "\n";
        }
        $output .= "<div class='row'>\n";
        $output .= "<span class='cell'></span>\n";
        $output .= $view->formRow($this->get('Submit'));
        $output .= "\n</div>\n</div>\n";
        return $output;
    }
}
