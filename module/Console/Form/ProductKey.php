<?php

/**
 * A form for entering an MS product key
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

namespace Console\Form;

/**
 * A form for entering an MS product key
 *
 * The product key is held in the 'Key' element.
 */
class ProductKey extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $key = new \Laminas\Form\Element\Text('Key');
        $key->setLabel('Product key (if different)');
        $this->add($key);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('OK');
        $this->add($submit);

        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add(
            array(
                'name' => 'Key',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'StringToUpper'),
                ),
                'validators' => array(
                    array('name' => 'Library\Validator\ProductKey'),
                ),
            )
        );
        $this->setInputFilter($inputFilter);
    }
}
