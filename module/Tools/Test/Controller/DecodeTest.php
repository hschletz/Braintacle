<?php
/**
 * Tests for Decode controller
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class DecodeTest extends AbstractControllerTest
{
    /**
     * InventoryDecode filter mock
     * @var \Protocol\Filter\InventoryDecode
     */
    protected $_inventoryDecode;

    public function setUp()
    {
        parent::setUp();
        $this->_inventoryDecode = $this->createMock('Protocol\Filter\InventoryDecode');
        $filterManager = static::$serviceManager->get('FilterManager');
        $filterManager->setAllowOverride(true);
        $filterManager->setService('Protocol\InventoryDecode', $this->_inventoryDecode);
    }

    public function testSuccess()
    {
        $this->_inventoryDecode->method('filter')->with('input data')->willReturn('output data');
        $inputFile = vfsStream::newFile('test')->withContent('input data')
                                               ->at(vfsStream::setup('root'))
                                               ->url();
        $this->_route->method('getMatchedParam')
                     ->willReturnMap(
                         array(
                            array('input_file', null, $inputFile),
                            array('output_file', null, null)
                         )
                     );
        $this->_console->expects($this->once())->method('write')->with('output data');

        $this->assertEquals(0, $this->_dispatch());
        $this->assertEquals(
            array(
                'root' => array(
                    'test' => 'input data',
                ),
            ),
            vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
        );
    }

    public function testSuccessWithOutputFile()
    {
        $this->_inventoryDecode->method('filter')->with('input data')->willReturn('output data');

        $dir = vfsStream::setup('root');
        $inputFile = vfsStream::newFile('test')->withContent('input data')->at($dir)->url();
        $outputFile = $dir->url() . '/output_file';

        $this->_route->method('getMatchedParam')
                     ->willReturnMap(
                         array(
                            array('input_file', null, $inputFile),
                            array('output_file', null, $outputFile)
                         )
                     );
        $this->_console->expects($this->never())->method('write');

        $this->assertEquals(0, $this->_dispatch());
        $this->assertEquals(
            array(
                'root' => array(
                    'test' => 'input data',
                    'output_file' => 'output data',
                ),
            ),
            vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
        );
    }

    public function testInputNotFile()
    {
        $inputFile = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();
        $this->_route->method('getMatchedParam')
                     ->willReturnMap(
                         array(
                            array('input_file', null, $inputFile),
                            array('output_file', null, null)
                         )
                     );
        $this->_console->expects($this->once())->method('writeLine')->with(
            'Input file does not exist or is not readable.'
        );

        $this->assertEquals(10, $this->_dispatch());
        $this->assertEquals(
            array(
                'root' => array(
                    'test' => array(),
                ),
            ),
            vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
        );
    }

    public function testInputFileNotReadable()
    {
        $inputFile = vfsStream::newFile('test', 0000)->at(vfsStream::setup('root'))->url();
        $this->_route->method('getMatchedParam')
                     ->willReturnMap(
                         array(
                            array('input_file', null, $inputFile),
                            array('output_file', null, null)
                         )
                     );
        $this->_console->expects($this->once())->method('writeLine')->with(
            'Input file does not exist or is not readable.'
        );

        $this->assertEquals(10, $this->_dispatch());
        $this->assertEquals(
            array(
                'root' => array(
                    'test' => null,
                ),
            ),
            vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
        );
    }

    public function testInvalidInputData()
    {
        $this->_inventoryDecode->method('filter')->willThrowException(new \InvalidArgumentException('message'));
        $inputFile = vfsStream::newFile('test')->at(vfsStream::setup('root'))->url();
        $this->_route->method('getMatchedParam')
                     ->willReturnMap(
                         array(
                            array('input_file', null, $inputFile),
                            array('output_file', null, null)
                         )
                     );
        $this->_console->expects($this->once())->method('writeLine')->with('message');

        $this->assertEquals(11, $this->_dispatch());
        $this->assertEquals(
            array(
                'root' => array(
                    'test' => null,
                ),
            ),
            vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
        );
    }
}
