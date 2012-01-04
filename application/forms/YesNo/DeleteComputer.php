<?php
/**
 * Form to ask before deleting a computer
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
 * Form to ask before deleting a computer
 *
 * After submission, only one of 'yes' or 'no' will show up in $_POST.
 * @package Forms
 */
class Form_YesNo_DeleteComputer extends Form_YesNo
{

    /**
     * Create elements
     */
    public function init()
    {
        $this->setAttrib('class', 'yesbutton_not_inline');

        $deleteInterfaces = new Zend_Form_Element_Checkbox('DeleteInterfaces');
        $deleteInterfaces->setLabel('Delete interfaces from network listing');
        $deleteInterfaces->setChecked(Model_Config::get('DefaultDeleteInterfaces'));
        $this->addElement($deleteInterfaces);

        parent::init();
    }

}
