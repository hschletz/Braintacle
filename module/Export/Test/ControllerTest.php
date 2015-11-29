<?php
/**
 * Tests for Controller
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

namespace Export\Test;

use \org\bovigo\vfs\vfsStream;

class ControllerTest extends \Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getService('ApplicationConfig'));
    }

    public function invalidRouteProvider()
    {
        return array(
            array(''),
            array('-v'),
            array('-validate'),
            array('-i directory'),
            array('-invalid directory'),
            array('-v -validate directory'),
        );
    }

    /**
     * @dataProvider invalidRouteProvider
     */
    public function testInvalidRoute($route)
    {
        $this->dispatch($route);
        $this->assertResponseStatusCode(1);
        $this->assertEquals(
            \Zend\Mvc\Application::ERROR_ROUTER_NO_MATCH,
            $this->getResponse()->getMetadata()['error']
        );
        $this->assertConsoleOutputContains('Usage:');
    }

    public function testExportActionSuccess()
    {
        $directory = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();

        $document1 = $this->getMock('Protocol\Message\InventoryRequest');
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('save')->with("$directory/filename1");
        $document1->expects($this->never())->method('isValid');

        $document2 = $this->getMock('Protocol\Message\InventoryRequest');
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('save')->with("$directory/filename2");
        $document2->expects($this->never())->method('isValid');

        $client1 = $this->getMock('Model\Client\Client');
        $client1->method('offsetGet')->with('IdString')->willReturn('client1');
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->getMock('Model\Client\Client');
        $client2->method('offsetGet')->with('IdString')->willReturn('client2');
        $client2->method('toDomDocument')->willReturn($document2);

        $clientManager = $this->getMockBuilder('Model\Client\ClientManager')->disableOriginalConstructor()->getMock();
        $clientManager->method('getClients')->with(null, 'IdString')->willReturn(array($client1, $client2));

        $console = $this->getMockBuilder('Zend\Console\Adapter\AbstractAdapter')
                        ->setMethods(array('writeLine'))
                        ->getMockForAbstractClass();
        $console->expects($this->exactly(2))->method('writeLine')->withConsecutive(
            array('Exporting client1'), array('Exporting client2')
        );

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Model\Client\ClientManager', $clientManager)
             ->setService('console', $console);

        $this->dispatch($directory);

        $this->assertResponseStatusCode(0);
    }

    public function testExportActionDirectoryNotExists()
    {
        $directory = vfsStream::setup('root')->url() . '/invalid';

        $clientManager = $this->getMockBuilder('Model\Client\ClientManager')->disableOriginalConstructor()->getMock();
        $clientManager->expects($this->never())->method('getClients');

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Model\Client\ClientManager', $clientManager);

        $this->dispatch($directory);

        $this->assertEquals(10, $this->getResponseStatusCode());
        $this->assertConsoleOutputContains("Directory '$directory' does not exist or is not writable.");
    }

    public function testExportActionDirectoryNotWritable()
    {
        $directory = vfsStream::newDirectory('test', 0000)->at(vfsStream::setup('root'))->url();

        $clientManager = $this->getMockBuilder('Model\Client\ClientManager')->disableOriginalConstructor()->getMock();
        $clientManager->expects($this->never())->method('getClients');

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Model\Client\ClientManager', $clientManager);

        $this->dispatch($directory);

        $this->assertEquals(10, $this->getResponseStatusCode());
        $this->assertConsoleOutputContains("Directory '$directory' does not exist or is not writable.");
    }

    public function testExportActionDirectoryIsFile()
    {
        $directory = vfsStream::newFile('test')->at(vfsStream::setup('root'))->url();

        $clientManager = $this->getMockBuilder('Model\Client\ClientManager')->disableOriginalConstructor()->getMock();
        $clientManager->expects($this->never())->method('getClients');

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Model\Client\ClientManager', $clientManager);

        $this->dispatch($directory);

        $this->assertEquals(10, $this->getResponseStatusCode());
        $this->assertConsoleOutputContains("Directory '$directory' does not exist or is not writable.");
    }

    public function exportActionValidateProvider()
    {
        return array(array('-validate'), array('-v'));
    }
    /**
     * @dataProvider exportActionValidateProvider
     */
    public function testExportActionValidate($option)
    {
        $directory = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();

        $document1 = $this->getMock('Protocol\Message\InventoryRequest');
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('save')->with("$directory/filename1");
        $document1->expects($this->once())->method('isValid')->willReturn(true);

        $document2 = $this->getMock('Protocol\Message\InventoryRequest');
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('save')->with("$directory/filename2");
        $document2->expects($this->once())->method('isValid')->willReturn(false);

        $document3 = $this->getMock('Protocol\Message\InventoryRequest');
        $document3->expects($this->never())->method('getFilename');
        $document3->expects($this->never())->method('save');
        $document3->expects($this->never())->method('isValid');

        $client1 = $this->getMock('Model\Client\Client');
        $client1->method('offsetGet')->with('IdString')->willReturn('client1');
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->getMock('Model\Client\Client');
        $client2->method('offsetGet')->with('IdString')->willReturn('client2');
        $client2->method('toDomDocument')->willReturn($document2);

        $client3 = $this->getMock('Model\Client\Client');
        $client3->expects($this->never())->method('offsetGet');
        $client3->expects($this->never())->method('toDomDocument');

        $clientManager = $this->getMockBuilder('Model\Client\ClientManager')->disableOriginalConstructor()->getMock();
        $clientManager->method('getClients')->with(null, 'IdString')->willReturn(array($client1, $client2, $client3));

        $console = $this->getMockBuilder('Zend\Console\Adapter\AbstractAdapter')
                        ->setMethods(array('writeLine'))
                        ->getMockForAbstractClass();
        $console->expects($this->exactly(2))->method('writeLine')->withConsecutive(
            array('Exporting client1'), array('Exporting client2')
        );

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Model\Client\ClientManager', $clientManager)
             ->setService('console', $console);

        $this->dispatch("$option $directory");

        $this->assertEquals(11, $this->getResponseStatusCode());
        $this->assertConsoleOutputContains('Validation failed for client2.');
    }
}
