<?php

/**
 * Tests for Export controller
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

use Model\Client\Client;
use Model\Client\ClientManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Protocol\Message\InventoryRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tools\Controller\Export;

class ExportTest extends \PHPUnit\Framework\TestCase
{
    public function testInvokeSuccessWithoutValidation()
    {
        $directory = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();

        $document1 = $this->createMock(InventoryRequest::class);
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('write')->with("$directory/filename1");
        $document1->expects($this->never())->method('isValid');

        $document2 = $this->createMock(InventoryRequest::class);
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('write')->with("$directory/filename2");
        $document2->expects($this->never())->method('isValid');

        $client1 = $this->createMock(Client::class);
        $client1->method('offsetGet')->with('IdString')->willReturn('client1');
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->createMock(Client::class);
        $client2->method('offsetGet')->with('IdString')->willReturn('client2');
        $client2->method('toDomDocument')->willReturn($document2);

        /** @var ClientManager|MockObject */
        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->method('getClients')->with(null, 'IdString')->willReturn([$client1, $client2]);

        $input = $this->createMock(InputInterface::class);
        $input->method('getArgument')->with('directory')->willReturn($directory);
        $input->method('getOption')->with('validate')->willReturn(false);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))->method('writeln')->withConsecutive(
            ['Exporting client1'],
            ['Exporting client2']
        );

        $controller = new Export($clientManager);
        $this->assertSame(Command::SUCCESS, $controller($input, $output));
    }

    public function testInvokeWithValidation()
    {
        $directory = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();

        $document1 = $this->createMock(InventoryRequest::class);
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('write')->with("$directory/filename1");
        $document1->expects($this->once())->method('isValid')->willReturn(true);

        $document2 = $this->createMock(InventoryRequest::class);
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('write')->with("$directory/filename2");
        $document2->expects($this->once())->method('isValid')->willReturn(false);

        $document3 = $this->createMock(InventoryRequest::class);
        $document3->expects($this->never())->method('getFilename');
        $document3->expects($this->never())->method('write');
        $document3->expects($this->never())->method('isValid');

        $client1 = $this->createMock(Client::class);
        $client1->method('offsetGet')->with('IdString')->willReturn('client1');
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->createMock(Client::class);
        $client2->method('offsetGet')->with('IdString')->willReturn('client2');
        $client2->method('toDomDocument')->willReturn($document2);

        $client3 = $this->createMock(Client::class);
        $client3->expects($this->never())->method('offsetGet');
        $client3->expects($this->never())->method('toDomDocument');

         /** @var ClientManager|MockObject */
         $clientManager = $this->createMock(ClientManager::class);
         $clientManager->method('getClients')
                       ->with(null, 'IdString')
                       ->willReturn([$client1, $client2, $client3]);

        $input = $this->createMock(InputInterface::class);
        $input->method('getArgument')->with('directory')->willReturn($directory);
        $input->method('getOption')->with('validate')->willReturn(true);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(3))->method('writeln')->withConsecutive(
            ['Exporting client1'],
            ['Exporting client2'],
            ['Validation failed for client2.']
        );

        $controller = new Export($clientManager);
        $this->assertEquals(11, $controller($input, $output));
    }

    public function invalidDirectoryProvider()
    {
        return [
            [vfsStream::newDirectory('test', 0000)], // not writable
            [vfsStream::newFile('test')], // file
        ];
    }

    /** @dataProvider invalidDirectoryProvider */
    public function testInvokeInvalidDirectory($directory)
    {
        $directory = $directory->at(vfsStream::setup('root'))->url();

        /** @var ClientManager|MockObject */
        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->never())->method('getClients');

        $input = $this->createMock(InputInterface::class);
        $input->method('getArgument')->with('directory')->willReturn($directory);
        $input->method('getOption')->with('validate')->willReturn(false);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with(
            "Directory '$directory' does not exist or is not writable."
        );

        $controller = new Export($clientManager);
        $this->assertEquals(10, $controller($input, $output));
    }

    public function testInvokeDirectoryDoesNotExist()
    {
        $directory = vfsStream::setup('root')->url() . '/invalid';

        /** @var ClientManager|MockObject */
        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->never())->method('getClients');

        $input = $this->createMock(InputInterface::class);
        $input->method('getArgument')->with('directory')->willReturn($directory);
        $input->method('getOption')->with('validate')->willReturn(false);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with(
            "Directory '$directory' does not exist or is not writable."
        );

        $controller = new Export($clientManager);
        $this->assertEquals(10, $controller($input, $output));
    }
}
