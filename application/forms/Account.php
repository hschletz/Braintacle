<?php
/**
 * Form for Braintacle user accounts
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
 * @package Forms
 */
/**
 * Form for Braintacle user accounts
 * @package Forms
 */
abstract class Form_Account extends Zend_Form
{

    /**
     * Create elements
     */
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('enctype', 'multipart/form-data');

        $id = new Zend_Form_Element_Text('Id');
        $id->setLabel('Login name')
           ->addValidator('StringLength', false, array(1, 255))
           ->setRequired(true)
           ->addFilter('StringTrim');
        $this->addElement($id);

        $password = new Zend_Form_Element_Password('Password');
        $password->setLabel('Password')
                 ->addValidator('StringLength', false, array(8, 255))
                 ->setRequired(true);
        $this->addElement($password);

        $password2 = new Zend_Form_Element_Password('PasswordRepeat');
        $password2->setLabel('Repeat password')
                  ->addValidator('Identical', false, array('token' => 'Password'))
                  ->setRequired(true)
                  ->setIgnore(true);
        $this->addElement($password2);

        $firstName = new Zend_Form_Element_Text('FirstName');
        $firstName->setLabel('First name')
                  ->addValidator('StringLength', false, array(1, 255))
                  ->addFilter('StringTrim')
                  ->addFilter('Null', 'string');
        $this->addElement($firstName);

        $lastName = new Zend_Form_Element_Text('LastName');
        $lastName->setLabel('Last name')
                 ->addValidator('StringLength', false, array(1, 255))
                 ->addFilter('StringTrim')
                 ->addFilter('Null', 'string');
        $this->addElement($lastName);

        $mailAddress = new Zend_Form_Element_Text('MailAddress');
        $mailAddress->setLabel('Mail address')
                    ->addValidator('StringLength', false, array(1, 255))
                    ->addValidator('EmailAddress')
                    ->addFilter('StringTrim')
                    ->addFilter('Null', 'string');
        $this->addElement($mailAddress);

        $comment = new Zend_Form_Element_Textarea('Comment');
        $comment->setLabel('Comment')
                ->addFilter('Null', 'string');
        $this->addElement($comment);

        // Submit button label is set in subclass
        $submit = new Zend_Form_Element_Submit('submit');
        $this->addElement($submit);
    }

}
