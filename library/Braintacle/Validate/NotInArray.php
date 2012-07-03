<?php
/**
 * Validate string to not match any value in a given array
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
 * @package Library
 */
/**
 * Validate string to not match any value in a given array
 *
 * This validator does the opposite of the ZF's InArray validator, but not all
 * functionality of InArray is supported. Simply pass the array of invalid
 * values to the constructor. Array keys will be ignored.
 * @package Library
 */
class Braintacle_Validate_NotInArray extends Zend_Validate_Abstract
{
    const IN_ARRAY = 'in_array';

    /**
     * Validation failure message template definitions
     * @var array
     */
    protected $_messageTemplates = array(
        self::IN_ARRAY => "'%value%' is in the list of invalid values.",
    );

    /**
     * Sets haystack
     * @param array $haystack
     * @throws InvalidArgumentException if $haystack is not an array.
     */
    public function __construct($haystack)
    {
        if (!is_array($haystack)) {
            throw new InvalidArgumentException('Array expected as parameter');
        }
        $this->_haystack = $haystack;
    }

    /**
     * Returns true if $value is not in the list of invalid values
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if (in_array($value, $this->_haystack)) {
            $this->_error(self::IN_ARRAY);
            return false;
        } else {
            return true;
        }
    }

}
