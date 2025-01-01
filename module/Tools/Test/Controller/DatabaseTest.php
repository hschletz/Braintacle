<?php

/**
 * Tests for Database controller
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Database\SchemaManager;
use Library\Validator\LogLevel as LogLevelValidator;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tools\Controller\Database as DatabaseController;

class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    public function testInvokeSuccess()
    {
        $input = $this->createStub(InputInterface::class);
        $input->method('getOption')->willReturnMap([
            ['loglevel', 'emergency'],
            ['prune', 'do_prune'],
        ]);

        $output = $this->createStub(OutputInterface::class);

        /** @var LogLevelValidator|MockObject */
        $validator = $this->createMock(LogLevelValidator::class);
        $validator->method('isValid')->with('emergency')->willReturn(true);

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())->method('pushHandler')->with($this->callback(
            function (StreamHandler $handler): bool {
                $this->assertEquals('php://stderr', $handler->getUrl());
                $this->assertEquals(Level::Emergency, $handler->getLevel());
                return true;
            }
        ));

        $schemaManager = $this->createMock(SchemaManager::class);
        $schemaManager->expects($this->once())->method('updateAll')->with('do_prune');

        $controller = new DatabaseController($schemaManager, $logger, $validator);
        $this->assertSame(Command::SUCCESS, $controller($input, $output));
    }

    public function testInvokeInvalidLogLevel()
    {
        $input = $this->createStub(InputInterface::class);
        $input->method('getOption')->willReturnMap([
            ['loglevel', 'log_level_input'],
            ['prune', 'do_prune'],
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with('error message');

        /** @var LogLevelValidator|MockObject */
        $validator = $this->createMock(LogLevelValidator::class);
        $validator->method('isValid')->with('log_level_input')->willReturn(false);
        $validator->method('getMessages')->willReturn([LogLevelValidator::LOG_LEVEL => 'error message']);

        $logger = $this->createStub(LoggerInterface::class);

        /** @var SchemaManager|MockObject */
        $schemaManager = $this->createMock(SchemaManager::class);
        $schemaManager->expects($this->never())->method('updateAll');

        $controller = new DatabaseController($schemaManager, $logger, $validator);
        $this->assertSame(Command::FAILURE, $controller($input, $output));
    }
}
