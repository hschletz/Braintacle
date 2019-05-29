<?php
/**
 * Base class for custom form renderers
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
 * Base class for custom form renderers
 *
 * Similar to the default form helper, but instead of iterating over form
 * elements, rendering is delegated to a custom method.
 */
abstract class AbstractHelper extends Form
{
    /** {@inheritdoc} */
    public function render(\Zend\Form\FormInterface $form)
    {
        if (method_exists($form, 'prepare')) {
            $form->prepare();
        }

        $formContent = $this->postMaxSizeExceeded();
        $formContent .= $this->openTag($form);
        $formContent .= "\n";
        $formContent .= $this->renderElements($form);
        $formContent .= "\n";
        $formContent .= $this->closeTag();
        $formContent .= "\n";
        return $formContent;
    }

    /**
     * Render form elements
     *
     * @param \Zend\Form\FormInterface $form
     */
    abstract public function renderElements(\Zend\Form\FormInterface $form);
}
