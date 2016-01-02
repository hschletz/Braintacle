<?php
/**
 * Tests for the Application class
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Test;

use \Library\Application;
use \org\bovigo\vfs\vfsStream;

/**
 * Tests for the Application class
 *
 * The methods init() and getPath() are not tested explicitly. They
 * are invoked as part of the bootstrap process which would most likely fail if
 * these methods didn't work correctly.
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests for environment detection methods
     *
     * The tests cover getEnvironment(), isProduction(), isDevelopment() and
     * isTest() with all relevant values for the APPLICATION_ENV environment
     * variable.
     */
    public function testEnvironment()
    {
        // Assume that the tests have been invoked with APPLICATION_ENV set to
        // "test". Otherwise the tests might be incomplete.
        $this->assertEquals('test', getenv('APPLICATION_ENV'));
        $this->assertEquals('test', Application::getEnvironment());
        $this->assertFalse(Application::isProduction());
        $this->assertTrue(Application::isDevelopment());
        $this->assertTrue(Application::isTest());

        // Unset APPLICATION_ENV, equivalent to "production"
        putenv('APPLICATION_ENV');
        $this->assertEquals('production', Application::getEnvironment());
        $this->assertTrue(Application::isProduction());
        $this->assertFalse(Application::isDevelopment());
        $this->assertFalse(Application::isTest());

        // Test "development" environment
        putenv('APPLICATION_ENV=development');
        $this->assertEquals('development', Application::getEnvironment());
        $this->assertFalse(Application::isProduction());
        $this->assertTrue(Application::isDevelopment());
        $this->assertFalse(Application::isTest());

        // Test invalid environment. Ensure that the variable is reset to its
        // default in either case.
        putenv('APPLICATION_ENV=invalid');
        try {
            Application::getEnvironment();
        } catch (\DomainException $expected) {
            $invalidEnvironmmentDetected = true;
        }
        // Reset to default.
        putenv('APPLICATION_ENV=test');
        if (!isset($invalidEnvironmmentDetected)) {
            $this->fail('Invalid environment was undetected.');
        }
    }
}
