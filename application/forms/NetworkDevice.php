<?php
/**
 * Form for identifying a network device
 *
 * $Id$
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * Form for identifying a network device
 *
 * The following text fields are provided:
 *
 * - Type
 * - Description
 * @package Forms
 */
class Form_NetworkDevice extends Zend_Form
{

    /**
     * Add form elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        // Translation must be turned off for this element to prevent the
        // application from trying to translate the values from the database.
        // The label will be translated manually.
        $categories = Model_NetworkDevice::getCategories();
        $type = new Zend_Form_Element_Select('Type');
        $type->setLabel($translate->_('Type'))
             ->setDisableTranslator(true)
             ->setMultiOptions(array_combine($categories, $categories));
        $this->addElement($type);

        $description = new Zend_Form_Element_Text('Description');
        $description->addFilter('StringTrim')
                    ->addValidator('StringLength', false, array(1, 255))
                    ->setRequired(true)
                    ->setLabel('Description');
        $this->addElement($description);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('OK');
        $this->addElement($submit);
    }

    /**
     * Populate form with values from a NetworkDevice object
     * @param Model_NetworkDevice Network device with values to put into the form
     */
    public function setValuesFromDevice($device)
    {
        foreach ($device as $property => $value) {
            $element = $this->getElement($property);
            if ($element) {
                $element->setValue($value);
            }
        }
    }
}
