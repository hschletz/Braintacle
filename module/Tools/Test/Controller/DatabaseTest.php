<?php
/**
 * Tests for Database controller
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

use Zend\Log\Logger;

class DatabaseTest extends AbstractControllerTest
{
    /**
     * Schema manager mock
     * @var \Database\SchemaManager
     */
    protected $_schemaManager;

    /**
     * Logger mock
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * Log writer mock
     * @var \Zend\Log\Writer\AbstractWriter
     */
    protected $_writer;

    public function setUp(): void
    {
        parent::setUp();

        $this->_schemaManager = $this->createMock('Database\SchemaManager');
        static::$serviceManager->setService('Database\SchemaManager', $this->_schemaManager);

        $this->_logger = $this->createMock('Zend\Log\Logger');
        static::$serviceManager->setService('Library\Logger', $this->_logger);

        $this->_writer = $this->createMock(\Zend\Log\Writer\AbstractWriter::class);
        static::$serviceManager->setService('Library\Log\Writer\StdErr', $this->_writer);
    }

    public function testDefaultOptions()
    {
        $this->_route->method('getMatchedParam')
                     ->withConsecutive(
                         ['loglevel', \Zend\Log\Logger::INFO],
                         ['prune', null],
                         ['p', null]
                     )->willReturnOnConsecutiveCalls(\Zend\Log\Logger::INFO, false, false);

        $this->_schemaManager->expects($this->once())->method('updateAll')->with(false);

        $this->assertEquals(0, $this->_dispatch());
    }

    public function testLoggerSetup()
    {
        $this->_writer->expects($this->once())
                      ->method('addFilter')
                      ->with('priority', ['priority' => \Zend\Log\Logger::DEBUG]);
        $this->_writer->expects($this->once())
                      ->method('setFormatter')
                      ->with('simple', ['format' => '%priorityName%: %message%']);

        $this->_logger->expects($this->once())->method('addWriter')->with($this->_writer);

        $this->_route->expects($this->exactly(3))
                     ->method('getMatchedParam')
                     ->withConsecutive(
                         array('loglevel', \Zend\Log\Logger::INFO),
                         array('prune', null),
                         array('p', null)
                     )->willReturnOnConsecutiveCalls(\Zend\Log\Logger::DEBUG, false, false);

        $this->assertEquals(0, $this->_dispatch());
    }

    public function pruneProvider()
    {
        return array(
            array(true, false),
            array(false, true)
        );
    }

    /**
     * @dataProvider pruneProvider
     */
    public function testPrune($longFlag, $shortFlag)
    {
        $this->_route->method('getMatchedParam')
                     ->withConsecutive(
                         array('loglevel', \Zend\Log\Logger::INFO),
                         array('prune', null),
                         array('p', null)
                     )->willReturnOnConsecutiveCalls(\Zend\Log\Logger::INFO, $longFlag, $shortFlag);

        $this->_schemaManager->expects($this->once())->method('updateAll')->with(true);

        $this->assertEquals(0, $this->_dispatch());
    }
}
