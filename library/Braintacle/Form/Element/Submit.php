<?php
/**
 * Drop-in replacement for Zend_Form_Element_Submit without name attribute
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
 *
 * @package Library
 */
/**
 * Drop-in replacement for Zend_Form_Element_Submit without name attribute
 *
 * Zend_Form_Element_Submit renders using Zend_View_Helper_FormSubmit which
 * unconditionally adds a 'name' attribute, causing the submit button to show up
 * in the submitted form data. This is useful for multiple submit buttons, but
 * has no value if there is only one. This is only a minor problem if the form
 * is submitted via POST, but for GET the button shows up in the URL.
 *
 * This replacement extends Zend_Form_Element_Submit with a different default
 * decorator set which does not generate the name attribute, resulting in a
 * nicer URL.
 *
 * @package Library
 */
class Braintacle_Form_Element_Submit extends Zend_Form_Element_Submit
{
    /** @ignore */
    function loadDefaultDecorators()
    {
        $this->setDecorators(
            array(
                'Tooltip',
                array(
                    'ViewHelper',
                    array('helper' => 'formSubmitSilent'),
                ),
                'DtDdWrapper',
            )
        );
    }
}
