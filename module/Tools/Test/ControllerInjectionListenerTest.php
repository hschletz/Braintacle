<?php

/**
 * Tests for ControllerInjectionListener
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Tools\Controller;
use Tools\ControllerInjectionListener;

class ControllerInjectionListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testInvokeWithoutService()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $command = $this->createMock(Command::class);
        $command->method('getName')->willReturn('noController');
        $command->expects($this->never())->method('setCode');

        $event = $this->createStub(ConsoleCommandEvent::class);
        $event->method('getCommand')->willReturn($command);

        $listener = new ControllerInjectionListener($container);
        $listener($event);
    }

    public static function invokeWithServiceProvider()
    {
        return [
            ['build', Controller\Build::class],
            ['database', Controller\Database::class],
            ['decode', Controller\Decode::class],
            ['export', Controller\Export::class],
            ['import', Controller\Import::class],
        ];
    }

    #[DataProvider('invokeWithServiceProvider')]
    public function testInvokeWithService(string $commandName, string $class)
    {
        $controller = $this->createStub($class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with($class)->willReturn($controller);

        $command = $this->createMock(Command::class);
        $command->method('getName')->willReturn($commandName);
        $command->expects($this->once())->method('setCode')->with($this->isInstanceOf($class));

        $event = $this->createStub(ConsoleCommandEvent::class);
        $event->method('getCommand')->willReturn($command);

        $listener = new ControllerInjectionListener($container);
        $listener($event);
    }
}
