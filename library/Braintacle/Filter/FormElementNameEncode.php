<?php
/**
 * Encode arbitrary string to be suitable as a Zend_Form_Element name
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
 * Encode arbitrary string to be suitable as a Zend_Form_Element name
 *
 * Zend_Form_Element restricts element names to a certain set of characters.
 * Invalid characters are silently stripped, bearing the risk of ambiguous names
 * and making it impossible to reconstruct the original name. To allow arbitrary
 * strings to be used as element names, this filter encodes the input string to
 * a character stream that contains only valid characters. The original string
 * can be reconstructed via Braintacle_Filter_FormElementNameDecode.
 *
 * The encoded string is not human readable. It is a base64 encoded version of
 * the input string with the special characters '+', '/' and '=' replaced by
 * '\_plus\_', '\_dash\_' and '_eq' (the underscore is a valid character that is
 * never part of raw base64 output).
 * @package Library
 */
class Braintacle_Filter_FormElementNameEncode implements Zend_Filter_Interface
{
    /** @ignore */
    public function filter($value)
    {
        return strtr(
            base64_encode($value),
            array(
                '+' => '_plus_',
                '/' => '_dash_',
                '=' => '_eq'
            )
        );
    }
}
