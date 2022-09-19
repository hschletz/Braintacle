<?php

/**
 * Form for identifying a network device
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
 * Form for identifying a network device
 *
 * The following fields are provided (both can be empty):
 *
 * - Type
 * - Description
 *
 * The init() method requires a \Model\Network\DeviceManager instance injected
 * via the DeviceManager option. The factory does this automatically.
 */
class NetworkDevice extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $type = new \Library\Form\Element\SelectSimple('Type');
        $type->setLabel('Type')
             ->setValueOptions($this->getOption('DeviceManager')->getTypes());
        $this->add($type);

        $description = new \Laminas\Form\Element\Text('Description');
        $description->setLabel('Description');
        $this->add($description);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('OK');
        $this->add($submit);

        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add(
            array(
                'name' => 'Description',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255),
                    ),
                ),
            )
        );
        $this->setInputFilter($inputFilter);
    }
}
