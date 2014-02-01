<?php
/**
 * Drop-in replacement for Zend_Form_Element_Hidden without label
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
 *
 * @package Library
 */
/**
 * Drop-in replacement for Zend_Form_Element_Hidden without label
 *
 * Zend_Form_Element_Hidden uses the default decorator set which includes a
 * `<dt>` element with a (typically empty) label. This requires explicitly
 * disabling the translator and still uses screen space for the label, resulting
 * in a visible gap in the form output.
 *
 * This replacement extends Zend_Form_Element_Hidden with a reduced default
 * decorator set which includes the element itself wrapped in a `<dd>` element.
 * This is required for valid HTML. No `<dt>` tag is created, which may seem odd,
 * but is valid.
 *
 * @package Library
 */
class Braintacle_Form_Element_Hidden extends Zend_Form_Element_Hidden
{
    /** @ignore */
    function loadDefaultDecorators()
    {
        $this->setDecorators(
            array(
                'ViewHelper',
                array(
                    'HtmlTag',
                    array('tag' => 'dd'),
                )
            )
        );
    }
}
