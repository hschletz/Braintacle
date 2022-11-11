<?php

/**
 * Base class for form view helpers
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

use Laminas\Form\FormInterface;

/**
 * Base class for form view helpers
 *
 * Extends default Laminas form helper with a prefixed message if post_max_size
 * has been exceeded.
 *
 * The render() method proxies to renderForm().
 *
 * @deprecated Render forms via templates
 */
class Form extends \Laminas\Form\View\Helper\Form
{
    /**
     * Return message if post_max_size has been exceeded.
     */
    public function postMaxSizeExceeded(): string
    {
        if (empty($_POST) and empty($_FILES) and strtoupper($_SERVER['REQUEST_METHOD'] ?? '') == 'POST') {
            return $this->getView()->htmlElement(
                'p',
                sprintf(
                    $this->getView()->translate('The post_max_size value of %s has been exceeded.'),
                    ini_get('post_max_size')
                ),
                array('class' => 'error')
            );
        } else {
            return '';
        }
    }

    public function render(FormInterface $form): string
    {
        return $this->renderForm($form);
    }

    /**
     * Render "form" element and its content.
     */
    public function renderForm(FormInterface $form, ...$renderContentArgs): string
    {
        $this->prepareForm($form);

        $formContent = $this->postMaxSizeExceeded();
        $formContent .= $this->openTag($form);
        $formContent .= $this->renderContent($form, ...$renderContentArgs);
        $formContent .= $this->closeTag();

        return $formContent;
    }

    /**
     * Render form content.
     *
     * Default implementation proxies to Fieldset helper. Subclasses may
     * override this method for custom markup. Extra arguments will be passed
     * from renderForm().
     */
    public function renderContent(FormInterface $form): string
    {
        return $this->getView()->consoleFormFieldset($form);
    }

    /**
     * Prepare form if prepare() method exists.
     */
    public function prepareForm(FormInterface $form): void
    {
        if (method_exists($form, 'prepare')) {
            $form->prepare();
        }
    }
}
