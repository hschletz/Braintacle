<?php
/**
 * Add Braintacle user account
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

        $this->get('Submit')->setLabel('Add');
    }
}
