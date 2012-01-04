<?php
/**
 * Login form
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 * @package Forms
 * @filesource
 */
/**
 * Login form
 * @package Forms
 */
class Form_Login extends Zend_Form
{

    /**
     * Create elements and custom decorators
     */
    public function init()
    {
        $username = new Zend_Form_Element_Text('userid');
        $username->addFilter('StringTrim')
                 ->addValidator('StringLength', false, array(1, 255))
                 ->setRequired(true)
                 ->setLabel('Username');
        $this->addElement($username);

        $password = new Zend_Form_Element_Password('password');
        $password->addFilter('StringTrim')
                 ->addValidator('StringLength', false, array(1, 50))
                 ->setRequired(true)
                 ->setLabel('Password');
        $this->addElement($password);

        $login = new Zend_Form_Element_Submit('login');
        $login->setRequired(false)
              ->setLabel('Login');
        $this->addElement($login);

        /* The controller will add a description to the form in case of a
           failed login attempt. The default decorator stack will not render
           the description.
           Rebuild the decorator stack with description. */
        $this->addDecorator('FormElements');
        $this->addDecorator(
            'HtmlTag', array('tag' => 'dl', 'class' => 'zend_form')
        );
        $this->addDecorator(
            'Description', array('placement' => 'prepend')
        );
        $this->addDecorator('Form');
    }
}
