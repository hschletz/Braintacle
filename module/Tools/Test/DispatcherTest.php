<?php
/**
 * Tests for Dispatcher
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

        $console = $this->createMock('Laminas\Console\Adapter\AdapterInterface');

        $container = $this->createMock('Laminas\ServiceManager\ServiceManager');
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

        $console = $this->createMock('Laminas\Console\Adapter\AdapterInterface');

        $container = $this->createMock('Laminas\ServiceManager\ServiceManager');
        $container->method('get')->willReturn(['key' => 'value']);
        $container->method('getAllowOverride')->willReturn('initialAllowOverride');
        $container->expects($this->exactly(2))->method('setAllowOverride')->withConsecutive(
            [true],
            ['initialAllowOverride']
        );
        $container->expects($this->once())->method('setService')->with(
            'ApplicationConfig',
            [
                'key' => 'value',
                'Library\UserConfig' => 'configFile',
            ]
        );

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

        $console = $this->createMock('Laminas\Console\Adapter\AdapterInterface');

        $container = $this->createMock('Laminas\ServiceManager\ServiceManager');
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
