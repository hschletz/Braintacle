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
use Laminas\Log\Filter\FilterInterface;
use Laminas\Log\Filter\Priority as PriorityFilter;
use Laminas\Log\Formatter\FormatterInterface;
use Laminas\Log\Formatter\Simple as SimpleFormatter;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\WriterInterface;
use Library\Filter\LogLevel as LogLevelFilter;
use Library\Validator\LogLevel as LogLevelValidator;
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
            ['loglevel', 'log_level_input'],
            ['prune', 'do_prune'],
        ]);

        $output = $this->createStub(OutputInterface::class);

        /** @var LogLevelValidator|MockObject */
        $validator = $this->createMock(LogLevelValidator::class);
        $validator->method('isValid')->with('log_level_input')->willReturn(true);

        /** @var LogLevelFilter|MockObject */
        $filter = $this->createMock(LogLevelFilter::class);
        $filter->method('filter')->with('log_level_input')->willReturn(Logger::EMERG);

        /** @var WriterInterface|MockObject */
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects($this->once())->method('addFilter')->with(
            $this->callback(function (FilterInterface $filter) {
                $this->assertInstanceOf(PriorityFilter::class, $filter);
                $this->assertTrue($filter->filter(['priority' => Logger::EMERG]));
                $this->assertFalse($filter->filter(['priority' => Logger::ALERT]));
                return true;
            })
        );
        $writer->expects($this->once())->method('setFormatter')->with(
            $this->callback(function (FormatterInterface $formatter) {
                $this->assertInstanceOf(SimpleFormatter::class, $formatter);
                $this->assertEquals('PRI: Message', $formatter->format([
                    'priorityName' => 'PRI',
                    'message' => 'Message',
                ]));
                return true;
            })
        );

        $logger = new Logger();
        $logger->addWriter($writer);
        $psrLogger = new PsrLoggerAdapter($logger);

        /** @var SchemaManager|MockObject */
        $schemaManager = $this->createMock(SchemaManager::class);
        $schemaManager->expects($this->once())->method('updateAll')->with('do_prune');

        $controller = new \Tools\Controller\Database($schemaManager, $psrLogger, $filter, $validator);
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

        /** @var LogLevelFilter|MockObject */
        $filter = $this->createMock(LogLevelFilter::class);
        $filter->expects($this->never())->method('filter');

        $logger = $this->createStub(LoggerInterface::class);

        /** @var SchemaManager|MockObject */
        $schemaManager = $this->createMock(SchemaManager::class);
        $schemaManager->expects($this->never())->method('updateAll');

        $controller = new DatabaseController($schemaManager, $logger, $filter, $validator);
        $this->assertSame(Command::FAILURE, $controller($input, $output));
    }
}
