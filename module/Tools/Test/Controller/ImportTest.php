<?php

/**
 * Tests for Import controller
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

namespace Tools\Test\Controller;

use Model\Client\ClientManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoke()
    {
        /** @var ClientManager|MockObject */
        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->once())->method('importFile')->with('input file');

        $input = $this->createMock(InputInterface::class);
        $input->method('getArgument')->with('filename')->willReturn('input file');

        $output = $this->createStub(OutputInterface::class);

        $controller = new \Tools\Controller\Import($clientManager);
        $this->assertSame(Command::SUCCESS, $controller($input, $output));
    }
}
