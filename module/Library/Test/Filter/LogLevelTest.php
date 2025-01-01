<?php

/**
 * Tests for LogLevel filter
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Test\Filter;

use DomainException;
use Laminas\Log\Logger;
use Library\Filter\LogLevel as LogLevelFilter;

class LogLevelTest extends \PHPUnit\Framework\TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf('Laminas\Filter\AbstractFilter', new \Library\Filter\LogLevel());
    }

    public static function filterProvider()
    {
        return array(
            array('Emerg', Logger::EMERG),
            array('Alert', Logger::ALERT),
            array('Crit', Logger::CRIT),
            array('Err', Logger::ERR),
            array('Warn', Logger::WARN),
            array('Notice', Logger::NOTICE),
            array('Info', Logger::INFO),
            array('Debug', Logger::DEBUG),
        );
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilter($value, $expectedResult)
    {
        $filter = new LogLevelFilter();
        $this->assertSame($expectedResult, $filter->filter($value));
    }

    public function testInvalidArgument()
    {
        $filter = new LogLevelFilter();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid log level: error');
        $filter->filter('error');
    }
}
