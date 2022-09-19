<?php

/**
 * Tests for LogLevel validator
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

namespace Library\Test\Validator;

use Library\Validator\LogLevel;

/**
 * Tests for LogLevel validator
 */
class LogLevelTest extends \PHPUnit\Framework\TestCase
{
    public function testValidation()
    {
        $validator = new LogLevel();
        $this->assertTrue($validator->isValid('Emerg'));
        $this->assertTrue($validator->isValid('Alert'));
        $this->assertTrue($validator->isValid('Crit'));
        $this->assertTrue($validator->isValid('Err'));
        $this->assertTrue($validator->isValid('Warn'));
        $this->assertTrue($validator->isValid('Notice'));
        $this->assertTrue($validator->isValid('Info'));
        $this->assertTrue($validator->isValid('Debug'));
        $this->assertFalse($validator->isValid('eErr')); // extra characters before valid string
        $this->assertFalse($validator->isValid('Error')); // extra characters after valid string
    }

    public function testMessage()
    {
        $validator = new LogLevel();
        $validator->isValid('Error');
        $this->assertEquals(
            array(LogLevel::LOG_LEVEL => "'Error' is not a valid log level"),
            $validator->getMessages()
        );
    }
}
