<?php

/**
 * Validate string to not match any value in a given array
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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
 */

namespace Library\Validator;

/**
 * Validate string to not match any value in a given array
 *
 * This validator does the opposite of Laminas' InArray validator, but not all
 * functionality of InArray is supported. The validator has 2 options:
 *
 * - Haystack (required): an array to validate a string against
 * - CaseSensitivity: One of the CASE_* constants defined by this validator,
 *   default is CASE_SENSITIVE.
 */
class NotInArray extends \Laminas\Validator\AbstractValidator
{
    /**
     * Case sensitive comparision
     **/
    const CASE_SENSITIVE = 0;

    /**
     * Case insensitive comparision
     **/
    const CASE_INSENSITIVE = 1;

    /**
     * Key for in_array message template
     */
    const IN_ARRAY = 'inArray';

    /**
     * Validation failure message template definitions
     * @var array
     */
    protected $messageTemplates = array(
        self::IN_ARRAY => "'%value%' is in the list of invalid values",
    );

    /**
     * Haystack option
     * @var array
     */
    protected $_haystack;

    /**
     * CaseSensitivity option
     * @var integer
     */
    protected $_caseSensitivity = self::CASE_SENSITIVE;

    /**
     * Set Haystack option
     *
     * @param array $haystack
     * @return $this
     */
    public function setHaystack($haystack)
    {
        $this->_haystack = $haystack;
        return $this;
    }

    /**
     * Get Haystack option
     * @return array
     */
    public function getHaystack()
    {
        if (!is_array($this->_haystack)) {
            throw new \RuntimeException('Haystack is not an array');
        }
        return $this->_haystack;
    }

    /**
     * Set CaseSensitivity option
     *
     * @param integer $caseSensitivity
     * @return $this
     */
    public function setCaseSensitivity($caseSensitivity)
    {
        if ($caseSensitivity !== self::CASE_SENSITIVE and $caseSensitivity !== self::CASE_INSENSITIVE) {
            throw new \InvalidArgumentException('Invalid value for caseSensitivity option: ' . $caseSensitivity);
        }
        $this->_caseSensitivity = $caseSensitivity;
        return $this;
    }

    /**
     * Get CaseSensitivity option
     * @return integer
     */
    public function getCaseSensitivity()
    {
        return $this->_caseSensitivity;
    }

    /**
     * Returns true if $value is not present in the haystack
     *
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $haystack = $this->getHaystack();
        $this->setValue($value);

        if ($this->getCaseSensitivity() == self::CASE_SENSITIVE) {
            if (!in_array($value, $haystack)) {
                return true;
            }
        } else {
            if (!preg_grep('/^' . preg_quote($value, '/') . '$/ui', $haystack)) {
                return true;
            }
        }
        $this->error(self::IN_ARRAY);
        return false;
    }
}
