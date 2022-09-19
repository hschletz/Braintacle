<?php

/**
 * Package update form renderer
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

namespace Console\View\Helper\Form\Package;

use Console\View\Helper\ConsoleScript;
use Console\View\Helper\Form\Form;
use Console\View\Helper\Form\FormHelperInterface;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\FormInterface;
use Laminas\Form\View\Helper\FormRow;

/**
 * Package update form renderer
 */
class Update extends Form implements FormHelperInterface
{
    public function render(FormInterface $form): string
    {
        $view = $this->getView();
        $view->plugin(ConsoleScript::class)('form_package.js');
        $view->plugin(Build::class)->initLabels($form);

        return $this->renderForm($form);
    }

    public function renderContent(FormInterface $form): string
    {
        $view = $this->getView();
        $view->plugin('formElementErrors')->setAttributes(['class' => 'errors']);

        $output = '';
        foreach ($form as $element) {
            if ($element->getName() == 'Deploy') {
                $output .= $this->renderDeployFieldset($element);
            } else {
                $output .= $view->plugin('formRow')($element);
            }
        }

        return $output;
    }

    /**
     * Render fieldset with deployment options.
     */
    public function renderDeployFieldset(FieldsetInterface $fieldset): string
    {
        $view = $this->getView();

        $output  = '<div class="label">';
        $output .= $view->escapeHtml($view->translate($fieldset->getLabel()));
        $output .= '</div>';
        $output .= $view->consoleFormFieldset($fieldset, FormRow::LABEL_APPEND);

        return $output;
    }
}
