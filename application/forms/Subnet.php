<?php
/**
 * Subnet properties form
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
 * Subnet properties form
 * @package Forms
 */
class Form_Subnet extends Zend_Form
{

    /**
     * Create elements
     */
    public function init()
    {
        $name = new Zend_Form_Element_Text('Name');
        $name->setLabel('Name')
             ->addValidator('StringLength', false, array(0, 255));
        $this->addElement($name);

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel('OK');
        $this->addElement($submit);
    }

    /**
     * Populate form with values from given subnet
     *
     * @param Model_Subnet $subnet Subnet
     **/
    public function setValuesFromSubnet(Model_Subnet $subnet)
    {
        $this->setDefault('Name', $subnet->getName());
    }
}
