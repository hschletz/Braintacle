<?php
/**
 * Base class for forms
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
 *
 */

namespace Console\Form;

/**
 * Base class for forms
 *
 * This base class extends \Zend\Form\Form with some convenience functionality:
 *
 * - The constructor sets the "class" attribute to "form" and a second value
 *   derived from the class name: Console\Form\Foo\Bar becomes form_foo_bar and
 *   so on. This allows general and individual styling of form content.
 *
 * - Automatic CSRF protection via hidden "_csrf" element.
 *
 * - Default rendering methods.
 */
class Form extends \Zend\Form\Form
{
    /** {@inheritdoc} */
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);

        $class = get_class($this);
        $class = strtr($class, '\\', '_');
        $class = substr($class, strpos($class, '_') + 1);
        $class = strtolower($class);
        $this->setAttribute('class', 'form ' . $class);

        $csrf = new \Zend\Form\Element\Csrf('_csrf');
        $csrf->setCsrfValidatorOptions(array('timeout' => null)); // Rely on session cleanup
        $this->add($csrf);
    }

    /**
     * Render the form
     *
     * @param \Zend\View\Renderer\PhpRenderer $view
     * @return string HTML form code
     */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $this->prepare();
        $output  = $view->form()->openTag($this);
        $output .= "\n<div>";
        $output .= $view->formHidden($this->get('_csrf'));
        $output .= "</div>\n";
        $output .= $this->renderFieldset($view, $this);
        $output .= "\n";
        $output .= $view->form()->closeTag();
        $output .= "\n";
        return $output;
    }

    /**
     * Render all elements from a fieldset
     *
     * This method iterates over all elements from the given fieldset and
     * renders them in a way appropriate for each element type. Subclasses with
     * more specialized rendering may extend or replace this method.
     *
     * @param \Zend\View\Renderer\PhpRenderer $view
     * @param \Zend\Form\Fieldset $fieldset
     * @return string HTML code
     */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset)
    {
        $output = "<div class='table'>\n";
        foreach ($fieldset as $element) {
            if ($element instanceof \Zend\Form\Element\Submit) {
                $output .= "<span class='cell'></span>\n";
                $output .= $view->formSubmit($element) . "\n";
            } elseif (!$element instanceof \Zend\Form\Element\Csrf) {
                $output .= $view->formRow($element, 'prepend', false) . "\n";
                if ($element->getMessages()) {
                    $output .= "<span class='cell'></span>\n";
                    $output .= $view->formElementErrors($element, array('class' => 'errors')) . "\n";
                }
            }
        }
        $output .= "</div>\n";
        return $output;
    }
}
