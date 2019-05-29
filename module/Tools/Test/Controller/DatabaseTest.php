<?php
/**
 * Tests for Database controller
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

    public function setUp()
    {
        parent::setUp();

        $this->_schemaManager = $this->createMock('Database\SchemaManager');
        static::$serviceManager->setService('Database\SchemaManager', $this->_schemaManager);

        $this->_logger = $this->createMock('Zend\Log\Logger');
        static::$serviceManager->setService('Library\Logger', $this->_logger);
    }

    public function testLogger()
    {
        $this->_logger->expects($this->once())->method('addWriter')->with(
            $this->callback(
                function ($writer) {
                    if (!$writer instanceof \Zend\Log\Writer\Stream) {
                        return false;
                    };
                    $stream = \PHPUnit\Framework\Assert::readAttribute($writer, 'stream');
                    if (!is_resource($stream) or get_resource_type($stream) != 'stream') {
                        return false;
                    }
                    if (stream_get_meta_data($stream)['uri'] != 'php://stderr') {
                        return false;
                    }

                    $filter = \PHPUnit\Framework\Assert::readAttribute($writer, 'filters')[0];
                    if (!$filter instanceof \Zend\Log\Filter\Priority) {
                        return false;
                    }
                    $priority = \PHPUnit\Framework\Assert::readAttribute($filter, 'priority');
                    if ($priority !== \Zend\Log\Logger::DEBUG) {
                        return false;
                    }
                    $operator = \PHPUnit\Framework\Assert::readAttribute($filter, 'operator');
                    if ($operator != '<=') {
                        return false;
                    }

                    return true;
                }
            )
        );

        $this->_schemaManager->expects($this->once())->method('updateAll')->with(false);

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
