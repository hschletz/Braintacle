<?php

/**
 * Tests for ConfigListener
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\AppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Tools\ConfigListener;

class ConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testInvokeWithoutConfigParam()
    {
        /** @var InputInterface|MockObject */
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->with('config')->willReturn(null);

        /** @var ConsoleCommandEvent|MockObject */
        $event = $this->createStub(ConsoleCommandEvent::class);
        $event->method('getInput')->willReturn($input);

        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->expects($this->never())->method('setFile');

        $configListener = new ConfigListener($appConfig);
        $configListener($event);
    }

    public function testInvokeWithConfigParam()
    {
        $configOption = 'configFile';

        /** @var InputInterface|MockObject */
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->with('config')->willReturn($configOption);

        /** @var ConsoleCommandEvent|MockObject */
        $event = $this->createStub(ConsoleCommandEvent::class);
        $event->method('getInput')->willReturn($input);

        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->expects($this->once())->method('setFile')->with($configOption);

        $configListener = new ConfigListener($appConfig);
        $configListener($event);
    }
}
