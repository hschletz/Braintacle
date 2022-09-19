<?php

/**
 * Tests for the Application class
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

namespace Library\Test;

use Library\Application;

/**
 * Tests for the Application class
 *
 * The methods init() and getPath() are not tested explicitly. They
 * are invoked as part of the bootstrap process which would most likely fail if
 * these methods didn't work correctly.
 */
class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetApplicationConfig()
    {
        $this->assertEquals(
            array(
                'modules' => array(
                    'Laminas\Filter',
                    'Laminas\Form',
                    'Laminas\I18n',
                    'Laminas\Log',
                    'Laminas\Mvc\I18n',
                    'Laminas\Mvc\Plugin\FlashMessenger',
                    'Laminas\Navigation',
                    'Laminas\Router',
                    'Laminas\Validator',
                    'moduleName',
                ),
                'module_listener_options' => array(
                    'module_paths' => array(realpath(__DIR__ . '/../..')),
                ),
            ),
            Application::getApplicationConfig('moduleName')
        );
    }
}
