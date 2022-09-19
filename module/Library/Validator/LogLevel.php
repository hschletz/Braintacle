<?php

/**
 * Validate string as a standard log level identifier
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
 * Validate string as a standard log level identifier
 */
class LogLevel extends \Laminas\Validator\AbstractValidator
{
    /**
     * Key for message template
     */
    const LOG_LEVEL = 'logLevel';

    /**
     * Validation failure message template definitions
     * @var string[]
     */
    protected $messageTemplates = array(
        self::LOG_LEVEL => "'%value%' is not a valid log level",
    );

    /**
     * Returns TRUE if $value is a valid log level (case insensitive)
     *
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);
        if (preg_match('/^(emerg|alert|crit|err|warn|notice|info|debug)$/i', $value)) {
            return true;
        } else {
            $this->error(self::LOG_LEVEL);
            return false;
        }
    }
}
