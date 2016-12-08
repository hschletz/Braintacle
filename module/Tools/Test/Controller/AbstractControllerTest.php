<?php
/**
 * Base class for controller tests
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

namespace Tools\Test\Controller;

abstract class AbstractControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Configured service manager set by bootstrap
     * @var \Zend\ServiceManager\ServiceManager
     */
    public static $serviceManager;

    /**
     * Route mock passed to dispatched method
     * @var \ZF\Console\Route
     */
    protected $_route;

    /**
     * Console mock passed to dispatched method
     * @var \Zend\Console\Adapter\AdapterInterface
     */
    protected $_console;

    public function setUp()
    {
        parent::setUp();

        $this->_route = $this->createMock('ZF\Console\Route');
        $this->_console = $this->createMock('Zend\Console\Adapter\AdapterInterface');
    }

    /**
     * Instantiate controller from service manager and invoke it with configured route and console mock
     *
     * @return integer Controller return value
     */
    protected function _dispatch()
    {
        $controller = static::$serviceManager->build(
            'Tools\Controller\\' . substr((new \ReflectionClass($this))->getShortName(), 0, -4)
        );
        return $controller($this->_route, $this->_console);
    }
}
