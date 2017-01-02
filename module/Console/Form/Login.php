<?php
/**
 * Login form
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Form;

/**
 * Login form
 *
 * Provides the "User" and "Password" fields. None of these are validated or
 * filtered - even empty values are allowed. It's up to the authentication
 * service to decide what is valid and what not.
 */
class Login extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $user = new \Zend\Form\Element\Text('User');
        $user->setLabel('Username');
        $this->add($user);

        $password = new \Zend\Form\Element\Password('Password');
        $password->setLabel('Password');
        $this->add($password);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Login');
        $this->add($submit);

        $inputFilter = $this->getInputFilter();
        $inputFilter->get('User')->setAllowEmpty(true);
        $inputFilter->get('Password')->setAllowEmpty(true);
    }

    /** {@inheritdoc} */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $view->placeholder('BodyOnLoad')->append('document.forms["form_login"]["User"].focus()');
        return parent::render($view);
    }
}
