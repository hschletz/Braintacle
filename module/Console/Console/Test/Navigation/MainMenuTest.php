<?php
/**
 * Tests for the main menu
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
 */

namespace Console\Test\Navigation;

use Library\Application;

/**
 * Tests for the main menu
 */
class MainMenuTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for valid factory output
     */
    public function testMainMenuFactory()
    {
        $this->assertInstanceOf(
            'Zend\Navigation\Navigation',
            Application::getService('Console\Navigation\MainMenuFactory')
        );
    }
}
