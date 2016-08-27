<?php
/**
 * Tests for Controller
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace DecodeInventory\Test;

use \org\bovigo\vfs\vfsStream;

class ControllerTest extends \Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getApplicationConfig('DecodeInventory', true));
    }

    public function invalidRouteProvider()
    {
        return array(
            array(''),
            array('input extra'),
            array('-i input'),
            array('-invalid input'),
            array('--invalid input'),
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

    public function testDecodeInventoryActionSuccess()
    {
        $inputFile = vfsStream::newFile('test')->withContent('input data')
                                               ->at(vfsStream::setup('root'))
                                               ->url();

        $inventoryDecode = $this->createMock('Protocol\Filter\InventoryDecode');
        $inventoryDecode->method('filter')->with('input data')->willReturn('output data');

        $this->getApplicationServiceLocator()
             ->get('FilterManager')
             ->setAllowOverride(true)
             ->setService('Protocol\InventoryDecode', $inventoryDecode);

        $this->dispatch($inputFile);

        $this->assertResponseStatusCode(0);
        $this->assertEquals('output data', $this->getResponse()->getContent());
    }

    public function testDecodeInventoryActionInputNotFile()
    {
        $inputFile = vfsStream::newDirectory('test')->at(vfsStream::setup('root'))->url();

        $this->dispatch($inputFile);

        $this->assertEquals(10, $this->getResponseStatusCode());
        $this->assertConsoleOutputContains("Input file does not exist or is not readable.\n");
    }

    public function testDecodeInventoryActionInputFileNotReadable()
    {
        $inputFile = vfsStream::newFile('test', 0000)->at(vfsStream::setup('root'))->url();

        $this->dispatch($inputFile);

        $this->assertEquals(10, $this->getResponseStatusCode());
        $this->assertConsoleOutputContains("Input file does not exist or is not readable.\n");
    }

    public function testDecodeInventoryActionInvalidInputData()
    {
        $inputFile = vfsStream::newFile('test')->at(vfsStream::setup('root'))->url();

        $inventoryDecode = $this->createMock('Protocol\Filter\InventoryDecode');
        $inventoryDecode->method('filter')->willThrowException(new \InvalidArgumentException('message'));

        $this->getApplicationServiceLocator()
             ->get('FilterManager')
             ->setAllowOverride(true)
             ->setService('Protocol\InventoryDecode', $inventoryDecode);

        $this->dispatch($inputFile);

        $this->assertEquals(11, $this->getResponseStatusCode());
        $this->assertConsoleOutputContains("message\n");
    }
}
