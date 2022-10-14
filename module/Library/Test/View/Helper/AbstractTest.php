<?php

/**
 * Base class for view helper tests
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

namespace Library\Test\View\Helper;

/**
 * Base class for view helper tests
 *
 * Tests for view helper classes can derive from this class for some convenience
 * functions. Additionally, the testHelperInterface() test is executed for all
 * derived tests.
 */
abstract class AbstractTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    /**
     * Service manager
     * @var \Laminas\ServiceManager\ServiceManager
     */
    public static $serviceManager;

    /**
     * View helper manager
     * @var \Laminas\View\HelperPluginManager
     */
    protected static $_helperManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$_helperManager = static::$serviceManager->get('ViewHelperManager');
    }

    /**
     * Get the name of the view helper, derived from the test class name
     *
     * @return string Helper name
     */
    protected function getHelperName()
    {
        // Derive helper name from test class name (minus namespace and 'Test' suffix)
        return lcfirst(substr(strrchr(get_class($this), '\\'), 1, -4));
    }

    /**
     * Get the name of the view helper class, derived from the test class name
     *
     * @return string Helper class name
     */
    protected static function getHelperClass()
    {
        // Derive helper class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_called_class()), 0, -4);
    }

    /**
     * Get view helper
     *
     * @param string $name Helper name (default: derive from test class name)
     */
    protected function getHelper($name = null): callable
    {
        if (!$name) {
            $name = $this->getHelperName();
        }
        return static::$_helperManager->build($name);
    }

    /**
     * Test if the helper is properly registered with the service manager
     */
    public function testHelperService()
    {
        // Uppercase
        $this->assertInstanceOf(
            static::getHelperClass(),
            $this->getHelper($this->getHelperName())
        );
        // Lowercase
        $this->assertInstanceOf(
            static::getHelperClass(),
            $this->getHelper(lcfirst($this->getHelperName()))
        );
    }
}
