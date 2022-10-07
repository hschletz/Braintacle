<?php

/**
 * Tests for Decode controller
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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use PHPUnit\Framework\MockObject\MockObject;
use Protocol\Filter\InventoryDecode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tools\Controller\Decode;

class DecodeTest extends \PHPUnit\Framework\TestCase
{
    public function testInvokeToStdOut()
    {
        /** @var InventoryDecode|MockObject */
        $inventoryDecode = $this->createMock(InventoryDecode::class);
        $inventoryDecode->method('filter')->with('input data')->willReturn('output data');

        $dir = vfsStream::setup('root');
        $inputFile = vfsStream::newFile('test')->withContent('input data')->at($dir)->url();

        $input = $this->createStub(InputInterface::class);
        $input->method('getArgument')->willReturnMap([
            ['input file', $inputFile],
            ['output file', null],
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('write')->with('output data');

        $controller = new Decode($inventoryDecode);
        $this->assertSame(Command::SUCCESS, $controller($input, $output));

        $this->assertEquals(
            [
                'root' => [
                    'test' => 'input data',
                ],
            ],
            vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure()
        );
    }

    public function testInvokeToFile()
    {
        /** @var InventoryDecode|MockObject */
        $inventoryDecode = $this->createMock(InventoryDecode::class);
        $inventoryDecode->method('filter')->with('input data')->willReturn('output data');

        $dir = vfsStream::setup('root');
        $inputFile = vfsStream::newFile('test')->withContent('input data')->at($dir)->url();

        $outputFile = $dir->url() . '/output_file';

        $input = $this->createStub(InputInterface::class);
        $input->method('getArgument')->willReturnMap([
            ['input file', $inputFile],
            ['output file', $outputFile],
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->never())->method('write');

        $controller = new Decode($inventoryDecode);
        $this->assertSame(Command::SUCCESS, $controller($input, $output));

        $this->assertEquals(
            [
                'root' => [
                    'test' => 'input data',
                    'output_file' => 'output data',
                ],
            ],
            vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure()
        );
    }

    public function invalidInputFileProvider()
    {
        return [
            [vfsStream::newDirectory('test'), []], // not a file
            [vfsStream::newFile('test', 0000), null], // not readable
        ];
    }

    /** @dataProvider invalidInputFileProvider */
    public function testInvokeInputNotFileOrNotReadable($inputFile, $filesystemObject)
    {
        /** @var InventoryDecode|MockObject */
        $inventoryDecode = $this->createMock(InventoryDecode::class);
        $inventoryDecode->expects($this->never())->method('filter');

        $input = $this->createStub(InputInterface::class);
        $input->method('getArgument')->willReturnMap([
            ['input file', $inputFile->at(vfsStream::setup('root'))->url()],
            ['output file', null],
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with('Input file does not exist or is not readable.');

        $controller = new Decode($inventoryDecode);
        $this->assertEquals(10, $controller($input, $output));

        $this->assertEquals(
            [
                'root' => [
                    'test' => $filesystemObject,
                ],
            ],
            vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure()
        );
    }

    public function testInvalidInputData()
    {
        /** @var InventoryDecode|MockObject */
        $inventoryDecode = $this->createMock(InventoryDecode::class);
        $inventoryDecode->method('filter')->willThrowException(new \InvalidArgumentException('message'));

        $inputFile = vfsStream::newFile('test')->at(vfsStream::setup('root'))->url();

        $input = $this->createStub(InputInterface::class);
        $input->method('getArgument')->willReturnMap([
            ['input file', $inputFile],
            ['output file', null],
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with('message');

        $controller = new Decode($inventoryDecode);
        $this->assertEquals(11, $controller($input, $output));

        $this->assertEquals(
            [
                'root' => [
                    'test' => null,
                ],
            ],
            vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure()
        );
    }
}
