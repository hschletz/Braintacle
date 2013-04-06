<?php
/**
 * A form for entering an MS product key
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
 * A form for entering an MS product key
 *
 * The product key is held in the 'key' element.
 * @package Forms
 */
class Form_ProductKey extends Zend_Form
{

    /**
     * Create elements
     */
    public function init()
    {
        $key = new Zend_Form_Element_Text('key');
        $key->setLabel('Product key (if different)')
            ->addFilter('StringTrim')
            ->addFilter('StringToUpper')
            ->addValidator(new Braintacle_Validate_ProductKey);
        $this->addElement($key);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('OK');
        $this->addElement($submit);
    }

}
