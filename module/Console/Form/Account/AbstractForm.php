<?php

/**
 * Base class for account forms
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

namespace Console\Form\Account;

use Laminas\Form\Element;

/**
 * Base class for account forms
 */
abstract class AbstractForm extends \Console\Form\Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $id = new Element\Text('Id');
        $id->setLabel('Login name');
        $this->add($id);
        $inputFilter->add(
            array(
                'name' => 'Id',
                'required' => true,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255)
                    )
                )
            )
        );

        $password = new Element\Password('Password');
        $password->setLabel('Password');
        $this->add($password);
        // Avoid exceeding the upper limit of the bcrypt hash method which
        // truncates passwords to 72 bytes. It is unaware of character encoding,
        // so we use the native string wrapper which counts bytes, not
        // characters.
        // The minimum length is checked with a different, encoding-aware
        // validator. So the length constraint is at least 8 characters, but not
        // more than 72 bytes.
        $passwordMax = new \Laminas\Validator\StringLength(
            array(
                'max' => \Model\Operator\AuthenticationAdapter::PASSWORD_MAX_BYTES,
                'encoding' => '8BIT',
            )
        );
        $passwordMax->setStringWrapper(new \Laminas\Stdlib\StringWrapper\Native());
        $passwordMax->setMessage(
            'The password is longer than %max% bytes',
            \Laminas\Validator\StringLength::TOO_LONG
        );
        $inputFilter->add(
            array(
                'name' => 'Password',
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('min' => 8)
                    ),
                    $passwordMax
                )
            )
        );

        $password2 = new Element\Password('PasswordRepeat');
        $password2->setLabel('Repeat password');
        $this->add($password2);
        $inputFilter->add(
            array(
                'name' => 'PasswordRepeat',
                'continue_if_empty' => true,
                'validators' => array(
                    array(
                        'name' => 'Identical',
                        'options' => array(
                            'token' => 'Password',
                            'message' => $this->_('The passwords do not match'),
                        ),
                    )
                ),
            )
        );

        $firstName = new Element\Text('FirstName');
        $firstName->setLabel('First name');
        $this->add($firstName);
        $inputFilter->add(
            array(
                'name' => 'FirstName',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'Null'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255)
                    )
                )
            )
        );

        $lastName = new Element\Text('LastName');
        $lastName->setLabel('Last name');
        $this->add($lastName);
        $inputFilter->add(
            array(
                'name' => 'LastName',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'Null'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255)
                    )
                )
            )
        );

        $mailAddress = new Element\Text('MailAddress');
        $mailAddress->setLabel('Mail address');
        $this->add($mailAddress);
        $inputFilter->add(
            array(
                'name' => 'MailAddress',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'Null'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255)
                    ),
                    array(
                        'name' => 'EmailAddress',
                        'options' => array('allow' => \Laminas\Validator\Hostname::ALLOW_ALL)
                    ),
                )
            )
        );

        $comment = new Element\Textarea('Comment');
        $comment->setLabel('Comment');
        $this->add($comment);
        $inputFilter->add(
            array(
                'name' => 'Comment',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'Null'),
                ),
            )
        );

        // Submit button text is set in subclass
        $submit = new \Library\Form\Element\Submit('Submit');
        $this->add($submit);

        $this->setInputFilter($inputFilter);
    }

    public function isValid(): bool
    {
        // Invoke parent implementation in any case to apply filters and
        // initialize messages.
        $isValid = parent::isValid();
        if ($this->getData()['MailAddress'] === null) {
            // A bug in the InputFilter causes NULL values to be passed to the
            // EmailAddress validator which will fail despite being optional.
            // Remove incorrect message.
            $mailAddress = $this->get('MailAddress');
            $messages = $mailAddress->getMessages();
            unset($messages[\Laminas\Validator\EmailAddress::INVALID]);
            $mailAddress->setMessages($messages);
            // Evaluate remaining messages.
            $isValid = !$this->getMessages();
        }
        return $isValid;
    }
}
