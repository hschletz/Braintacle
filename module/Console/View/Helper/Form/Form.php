<?php
/**
 * Base class for form view helpers
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
 * Base class for form view helpers
 *
 * Extends default Laminas form helper with a prefixed message if post_max_size
 * has been exceeded.
 */
class Form extends \Laminas\Form\View\Helper\Form
{
    /**
     * Return message if post_max_size has been exceeded.
     *
     * @return string
     */
    public function postMaxSizeExceeded()
    {
        if (empty($_POST) and empty($_FILES) and strtoupper(@$_SERVER['REQUEST_METHOD']) == 'POST') {
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

    /** {@inheritdoc} */
    public function render(\Laminas\Form\FormInterface $form)
    {
        if (method_exists($form, 'prepare')) {
            $form->prepare();
        }

        $formContent = $this->postMaxSizeExceeded();
        $formContent .= $this->openTag($form);
        $formContent .= $this->getView()->consoleFormFieldset($form);
        $formContent .= $this->closeTag();

        return $formContent;
    }
}
