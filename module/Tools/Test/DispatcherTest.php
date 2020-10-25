<?php
/**
 * Tests for Dispatcher
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Tools\Test;

class DispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testDispatchWithoutConfigParam()
    {
        $route = $this->createMock('ZF\Console\Route');
        $route->method('getMatchedParam')->with('config')->willReturn(null);
        $route->method('getName')->willReturn('routeName');

        $console = $this->createMock('Zend\Console\Adapter\AdapterInterface');

        $container = $this->createMock('Zend\ServiceManager\ServiceManager');
        $container->expects($this->never())->method('setAllowOverride');
        $container->expects($this->never())->method('setService');

        $dispatcher = new \Tools\Dispatcher($container);
        $dispatcher->map(
            'routeName',
            function ($route, $console) {
                return 42;
            }
        );
        $this->assertEquals(42, $dispatcher->dispatch($route, $console));
    }

    public function testDispatchWithConfigParam()
    {
        $route = $this->createMock('ZF\Console\Route');
        $route->method('getMatchedParam')->with('config')->willReturn('configFile');
        $route->method('getName')->willReturn('routeName');

        $console = $this->createMock('Zend\Console\Adapter\AdapterInterface');

        $container = $this->createMock('Zend\ServiceManager\ServiceManager');
        $container->expects($this->at(0))->method('get')->willReturn(array('key' => 'value'));
        $container->expects($this->at(1))->method('getAllowOverride')->willReturn('initialAllowOverride');
        $container->expects($this->at(2))->method('setAllowOverride')->with(true);
        $container->expects($this->at(3))->method('setService')->with(
            'ApplicationConfig',
            array(
                'key' => 'value',
                'Library\UserConfig' => 'configFile',
            )
        );
        $container->expects($this->at(4))->method('setAllowOverride')->with('initialAllowOverride');

        $dispatcher = new \Tools\Dispatcher($container);
        $dispatcher->map(
            'routeName',
            function ($route, $console) {
                return 42;
            }
        );
        $this->assertEquals(42, $dispatcher->dispatch($route, $console));
    }

    public function testDispatchOverridingExistingConfigThrowsException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Library\UserConfig already set');

        $route = $this->createMock('ZF\Console\Route');
        $route->method('getMatchedParam')->with('config')->willReturn('configFile');

        $console = $this->createMock('Zend\Console\Adapter\AdapterInterface');

        $container = $this->createMock('Zend\ServiceManager\ServiceManager');
        $container->method('get')->with('ApplicationConfig')->willReturn(
            array(
                'key' => 'value',
                'Library\UserConfig' => 'existingConfig',
            )
        );
        $container->expects($this->never())->method('setAllowOverride');
        $container->expects($this->never())->method('setService');

        $dispatcher = new \Tools\Dispatcher($container);
        $dispatcher->dispatch($route, $console);
    }
}
