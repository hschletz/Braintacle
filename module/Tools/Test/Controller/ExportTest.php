<?php
/**
 * Tests for Export controller
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

class ExportTest extends AbstractControllerTest
{
    /**
     * Client manager mock
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    public function setUp()
    {
        parent::setUp();
        $this->_clientManager = $this->createMock('Model\Client\ClientManager');
        static::$serviceManager->setService('Model\Client\ClientManager', $this->_clientManager);
    }

    public function testSuccess()
    {
        $directory = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();

        $document1 = $this->createMock('Protocol\Message\InventoryRequest');
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('save')->with("$directory/filename1");
        $document1->expects($this->never())->method('isValid');

        $document2 = $this->createMock('Protocol\Message\InventoryRequest');
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('save')->with("$directory/filename2");
        $document2->expects($this->never())->method('isValid');

        $client1 = $this->createMock('Model\Client\Client');
        $client1->method('offsetGet')->with('IdString')->willReturn('client1');
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('offsetGet')->with('IdString')->willReturn('client2');
        $client2->method('toDomDocument')->willReturn($document2);

        $this->_clientManager->method('getClients')->with(null, 'IdString')->willReturn(array($client1, $client2));

        $this->_route->method('getMatchedParam')
                     ->withConsecutive(
                         array('directory', null),
                         array('validate', null),
                         array('v', null)
                     )
                     ->willReturnOnConsecutiveCalls($directory, false, false);

        $this->_console->expects($this->exactly(2))->method('writeLine')->withConsecutive(
            array('Exporting client1'),
            array('Exporting client2')
        );

        $this->assertEquals(0, $this->_dispatch());
    }

    public function testDirectoryDoesNotExist()
    {
        $directory = vfsStream::setup('root')->url() . '/invalid';

        $this->_clientManager->expects($this->never())->method('getClients');

        $this->_route->method('getMatchedParam')
                     ->withConsecutive(
                         array('directory', null),
                         array('validate', null),
                         array('v', null)
                     )
                     ->willReturnOnConsecutiveCalls($directory, false, false);

        $this->_console->expects($this->once())->method('writeLine')->with(
            "Directory '$directory' does not exist or is not writable."
        );

        $this->assertEquals(10, $this->_dispatch());
    }

    public function testDirectoryNotWritable()
    {
        $directory = vfsStream::newDirectory('test', 0000)->at(vfsStream::setup('root'))->url();

        $this->_clientManager->expects($this->never())->method('getClients');

        $this->_route->method('getMatchedParam')
                     ->withConsecutive(
                         array('directory', null),
                         array('validate', null),
                         array('v', null)
                     )
                     ->willReturnOnConsecutiveCalls($directory, false, false);

        $this->_console->expects($this->once())->method('writeLine')->with(
            "Directory '$directory' does not exist or is not writable."
        );

        $this->assertEquals(10, $this->_dispatch());
    }

    public function testDirectoryIsFile()
    {
        $directory = vfsStream::newFile('test')->at(vfsStream::setup('root'))->url();

        $this->_clientManager->expects($this->never())->method('getClients');

        $this->_route->method('getMatchedParam')
                     ->withConsecutive(
                         array('directory', null),
                         array('validate', null),
                         array('v', null)
                     )
                     ->willReturnOnConsecutiveCalls($directory, false, false);

        $this->_console->expects($this->once())->method('writeLine')->with(
            "Directory '$directory' does not exist or is not writable."
        );

        $this->assertEquals(10, $this->_dispatch());
    }

    public function validateProvider()
    {
        return array(
            array(true, false),
            array(false, true)
        );
    }
    /**
     * @dataProvider validateProvider
     */
    public function testValidate($longFlag, $shortFlag)
    {
        $directory = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();

        $document1 = $this->createMock('Protocol\Message\InventoryRequest');
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('save')->with("$directory/filename1");
        $document1->expects($this->once())->method('isValid')->willReturn(true);

        $document2 = $this->createMock('Protocol\Message\InventoryRequest');
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('save')->with("$directory/filename2");
        $document2->expects($this->once())->method('isValid')->willReturn(false);

        $document3 = $this->createMock('Protocol\Message\InventoryRequest');
        $document3->expects($this->never())->method('getFilename');
        $document3->expects($this->never())->method('save');
        $document3->expects($this->never())->method('isValid');

        $client1 = $this->createMock('Model\Client\Client');
        $client1->method('offsetGet')->with('IdString')->willReturn('client1');
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->createMock('Model\Client\Client');
        $client2->method('offsetGet')->with('IdString')->willReturn('client2');
        $client2->method('toDomDocument')->willReturn($document2);

        $client3 = $this->createMock('Model\Client\Client');
        $client3->expects($this->never())->method('offsetGet');
        $client3->expects($this->never())->method('toDomDocument');

        $this->_clientManager->method('getClients')
                             ->with(null, 'IdString')
                             ->willReturn(array($client1, $client2, $client3));

        $this->_route->method('getMatchedParam')
                     ->withConsecutive(
                         array('directory', null),
                         array('validate', null),
                         array('v', null)
                     )
                     ->willReturnOnConsecutiveCalls($directory, $longFlag, $shortFlag);

        $this->_console->expects($this->exactly(3))->method('writeLine')->withConsecutive(
            array('Exporting client1'),
            array('Exporting client2'),
            array('Validation failed for client2.')
        );

        $this->assertEquals(11, $this->_dispatch());
    }
}
