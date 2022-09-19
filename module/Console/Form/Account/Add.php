<?php

/**
 * Add Braintacle user account
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

use Laminas\Validator\NotEmpty;

/**
 * Add Braintacle user account
 */
class Add extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $inputFilter = $this->getInputFilter();

        // User name must not exist
        $idNotExistsValidator = new \Library\Validator\NotInArray(
            array(
                'haystack' => $this->getOption('operatorManager')->getAllIds(),
                'caseSensitivity' => \Library\Validator\NotInArray::CASE_INSENSITIVE,
            )
        );
        $inputFilter->add(
            array(
                'name' => 'Id',
                'validators' => array($idNotExistsValidator),
            )
        );

        // Default NotEmpty validator would behave inconsistently with
        // all-whitespace passwords. While that would be an odd password choice,
        // password quality check beyond length validation is not this form's
        // purpose.
        $inputFilter->get('Password')->getValidatorChain()->prependByName(
            'NotEmpty',
            array('type' => NotEmpty::STRING | NotEmpty::EMPTY_ARRAY | NotEmpty::NULL),
            true // Stop here to avoid additional message by StringLength validator
        );

        $this->get('Submit')->setLabel('Add');
    }
}
