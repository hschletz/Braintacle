<?php
/**
 * Form for identifying a network device
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
 * The init() method requires a \Model_NetworkDevice instance injected via the
 * NetworkDeviceModel option. The factory does this automatically.
 */
class NetworkDevice extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        $categories = $this->getOption('NetworkDeviceModel')->getCategories();

        $type = new \Zend\Form\Element\Select('Type');
        $type->setLabel('Type')
             ->setValueOptions(array_combine($categories, $categories)); // Use as both value and label
        $this->add($type);

        $description = new \Zend\Form\Element\Text('Description');
        $description->setLabel('Description');
        $this->add($description);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setText('OK');
        $this->add($submit);

        $inputFilter = new \Zend\InputFilter\InputFilter;
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
