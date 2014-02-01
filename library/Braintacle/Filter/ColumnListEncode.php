<?php
/**
 * Encode string to be used as part of ComputerController's "columns" parameter
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
 * Encode string to be used as part of ComputerController's "columns" parameter
 *
 * ComputerController's "index" action recognizes a "columns" parameter which
 * contains a comma-separated list of column names. If a column name contains
 * commas, as can happen with userdefined names, the commas must be escaped by a
 * backslash to distinct from a list separator. Literal backslashes must be
 * escaped by a second backslash, even if not followed by a comma.
 *
 * This filter encodes single list elements, not the entire list. There is no
 * corresponding decoding filter because the decoding process does not fit well
 * in the filter architecture.
 * @package Library
 */
class Braintacle_Filter_ColumnListEncode implements Zend_Filter_Interface
{
    /** @ignore */
    public function filter($value)
    {
        return strtr(
            $value,
            array(
                ',' => '\\,',
                '\\' => '\\\\',
            )
        );
    }
}
