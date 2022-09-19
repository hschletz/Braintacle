<?php

/**
 * Form helper for legacy forms which implement form rendering themselves
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
 * Form helper for legacy forms which implement form rendering themselves
 *
 * Invoked by \Console\Form\Form::render(), renders the _csrf element if
 * present and calls the form's renderFieldset() method to do the rest.
 *
 * New forms should not implement render() or renderFieldset(), but delegate
 * rendering entirely to a view helper. Those form's render() method should no
 * longer be called. The view helper should be invoked directly instead.
 *
 * @deprecated
 */
class LegacyHelper extends Form
{
    public function renderContent(FormInterface $form): string
    {
        $formContent = '';
        if ($form->has('_csrf')) {
            $formContent .= $this->view->htmlElement(
                'div',
                $this->view->formHidden($form->get('_csrf')),
                array(),
                true
            );
            $formContent .= "\n";
        }
        $formContent .= $form->renderFieldset($this->view, $form);
        return $formContent;
    }
}
