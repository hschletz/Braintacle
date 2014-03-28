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
}
