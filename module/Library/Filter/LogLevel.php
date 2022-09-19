<?php

/**
 * Convert log level descriptor strings (case insensitive) to Laminas\Log\Logger constants
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

namespace Library\Filter;

use DomainException;
use Laminas\Log\Logger;

/**
 * Convert log level descriptor strings (case insensitive) to Laminas\Log\Logger
 * constants
 */
class LogLevel extends \Laminas\Filter\AbstractFilter
{
    /**
     * Priority map
     * @var integer[]
     */
    protected $_priorities = array(
        'emerg' => Logger::EMERG,
        'alert' => Logger::ALERT,
        'crit' => Logger::CRIT,
        'err' => Logger::ERR,
        'warn' => Logger::WARN,
        'notice' => Logger::NOTICE,
        'info' => Logger::INFO,
        'debug' => Logger::DEBUG,
    );

    /** {@inheritdoc} */
    public function filter($value)
    {
        $value = strtolower($value);
        if (!isset($this->_priorities[$value])) {
            throw new DomainException('Invalid log level: ' . $value);
        }

        return $this->_priorities[$value];
    }
}
