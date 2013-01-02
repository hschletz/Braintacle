<?php
/**
 * Validate string to not match any value in a given array
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
 * @package Library
 */
/**
 * Validate string to not match any value in a given array
 *
 * This validator does the opposite of the ZF's InArray validator, but not all
 * functionality of InArray is supported. Simply pass the array of invalid
 * values to the constructor. Array keys will be ignored. Case sensitivity
 * depends on the second argument to the constructor.
 * @package Library
 */
class Braintacle_Validate_NotInArray extends Zend_Validate_Abstract
{
    /**
     * Case sensitive comparision
     **/
    const CASE_SENSITIVE = 0;

    /**
     * Case insensitive comparision
     **/
    const CASE_INSENSITIVE = 1;

    const IN_ARRAY = 'in_array';

    /**
     * Validation failure message template definitions
     * @var array
     */
    protected $_messageTemplates = array(
        self::IN_ARRAY => "'%value%' is in the list of invalid values.",
    );

    /**
     * Sets haystack and options
     * @param array $haystack
     * @param integer $case CASE_SENSITIVE (default) or CASE_INSENSITIVE
     * @throws InvalidArgumentException if $haystack is not an array or $case has invalid value
     */
    public function __construct($haystack, $case = self::CASE_SENSITIVE)
    {
        if (!is_array($haystack)) {
            throw new InvalidArgumentException('Array expected as parameter');
        }
        if ($case != self::CASE_SENSITIVE and $case != self::CASE_INSENSITIVE) {
            throw new InvalidArgumentException('Invalid value for $case: ' . $case);
        }
        $this->_haystack = $haystack;
        $this->_case = $case;
    }

    /**
     * Returns true if $value is not in the list of invalid values
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if ($this->_case == self::CASE_SENSITIVE) {
            if (in_array($value, $this->_haystack)) {
                $this->_error(self::IN_ARRAY);
                return false;
            } else {
                return true;
            }
        } else {
            $pattern = '#^' . preg_quote($value, '#') . '$#ui';
            foreach ($this->_haystack as $element) {
                if (preg_match($pattern, $element)) {
                    $this->_error(self::IN_ARRAY);
                    return false;
                }
            }
            return true;
        }
    }

}
