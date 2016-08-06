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
    public function testGetApplicationConfigWithoutTestEnvironment()
    {
        $this->assertEquals(
            array(
                'modules' => array('moduleName'),
                'module_listener_options' => array(
                    'module_paths' => array(realpath(__DIR__ . '/../..')),
                ),
            ),
            Application::getApplicationConfig('moduleName', false)
        );
    }

    public function testGetApplicationConfigWithTestEnvironment()
    {
        $this->assertEquals(
            array(
                'modules' => array('moduleName'),
                'module_listener_options' => array(
                    'module_paths' => array(realpath(__DIR__ . '/../..')),
                ),
                'Library\UserConfig' => array(
                    'debug' => array(
                        'display backtrace' => true,
                    ),
                ),
            ),
            Application::getApplicationConfig('moduleName', true)
        );
    }

    public function testEnvironment()
    {
        // Assume that the tests have been invoked with APPLICATION_ENV set to
        // "development". Otherwise the tests might be incomplete.
        $this->assertEquals('development', getenv('APPLICATION_ENV'));
        $this->assertEquals('development', Application::getEnvironment());
        $this->assertTrue(Application::isDevelopment());

        // Unset APPLICATION_ENV, equivalent to "production"
        putenv('APPLICATION_ENV');
        $this->assertEquals('production', Application::getEnvironment());
        $this->assertFalse(Application::isDevelopment());

        // Test invalid environment. Ensure that the variable is reset to its
        // default in either case.
        putenv('APPLICATION_ENV=test');
        try {
            Application::getEnvironment();
        } catch (\DomainException $expected) {
            $invalidEnvironmmentDetected = true;
        }
        // Reset to default.
        putenv('APPLICATION_ENV=development');
        if (!isset($invalidEnvironmmentDetected)) {
            $this->fail('Invalid environment was undetected.');
        }
    }
}
