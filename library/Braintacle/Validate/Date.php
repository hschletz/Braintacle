<?php
/**
 * Strict validation of date input
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
 * Strict validation of date input
 *
 * This class checks for valid date (not timestamp!) input that can be
 * interpreted by Zend_Date. Specifying a time of day is invalid. This can avoid
 * confusion when the user enters a full timestamp that would be accepted by
 * Zend_Date, but the application would ignore the time part.
 *
 * The application-wide locale is always used for the date format.
 * @package Library
 */
class Braintacle_Validate_Date extends Zend_Validate_Abstract
{
    const FORMAT = 'format';
    const TIMEOFDAY = 'timeofday';

    /**
     * Validation failure message template definitions
     * @var array
     */
    protected $_messageTemplates = array(
        self::FORMAT => "'%value%' is not a valid date",
        self::TIMEOFDAY => 'Only date can be given, not time of day',
    );

    /**
     * Returns true if $value is a valid date without time specification.
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // Check for valid input format. Year part can have 2 or 4 digits.
        if (Zend_Date::isDate($value, Zend_Date::DATE_SHORT)) {
            // OK, now check if time of day has been given (i.e. it's different from midnight).
            $value = new Zend_Date($value);
            // getDate() does not work, see ZF-4490. This workaround does.
            $date = clone $value;
            $date->setTime('00:00:00', 'HH:mm:ss');
            if (!$value->equals($date)) {
                $this->_error(self::TIMEOFDAY);
                return false;
            }
        } else {
            $this->_error(self::FORMAT);
            return false;
        }

        // All checks passed.
        return true;
    }

}
