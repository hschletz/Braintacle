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

namespace DatabaseManager\Test;

use Zend\Log\Logger;

class ControllerTest extends \Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getApplicationConfig('DatabaseManager', true));
    }

    public function invalidRouteProvider()
    {
        return array(
            array('--loglevel'),
            array('--loglevel='),
        );
    }

    /**
     * @dataProvider invalidRouteProvider
     */
    public function testInvalidRoute($route)
    {
        $validator = $this->createMock('Library\Validator\LogLevel');
        $validator->expects($this->never())->method('isValid');
        $this->getApplicationServiceLocator()->get('ValidatorManager')->setService('Library\LogLevel', $validator);

        $this->dispatch($route);
        $this->assertResponseStatusCode(1);
        $this->assertEquals(
            \Zend\Mvc\Application::ERROR_ROUTER_NO_MATCH,
            $this->getResponse()->getMetadata()['error']
        );
        $this->assertConsoleOutputContains('Usage:');
    }

    public function testInvalidLogLevel()
    {
        $validator = $this->createMock('Library\Validator\LogLevel');
        $validator->expects($this->once())->method('isValid')->with('0')->willReturn(false);
        $this->getApplicationServiceLocator()->get('ValidatorManager')->setService('Library\LogLevel', $validator);

        $this->dispatch('--loglevel=0');
        $this->assertResponseStatusCode(1);
        $this->assertEquals(
            \Zend\Mvc\Application::ERROR_ROUTER_NO_MATCH,
            $this->getResponse()->getMetadata()['error']
        );
        $this->assertConsoleOutputContains('Usage:');
    }

    public function schemaManagerActionProvider()
    {
        return array(
            array('', Logger::INFO, false),
            array('--loglevel=debug', Logger::DEBUG, false),
            array('--loglevel=debug --prune', Logger::DEBUG, true),
            array('--prune --loglevel=debug', Logger::DEBUG, true),
            array('--prune', Logger::INFO, true),
        );
    }

    /**
     * @dataProvider schemaManagerActionProvider
     */
    public function testSchemaManagerAction($cmdLine, $expectedPriority, $expectedPrune)
    {
        $serviceManager = $this->getApplicationServiceLocator();

        $validator = $this->createMock('Library\Validator\LogLevel');
        $filter = $this->createMock('Library\Filter\LogLevel');
        if (strpos($cmdLine, '--loglevel=debug') !== false) {
            $validator->expects($this->once())->method('isValid')->with('debug')->willReturn(true);
            $filter->expects($this->once())->method('filter')->with('debug')->willReturn($expectedPriority);
        } else {
            $validator->expects($this->never())->method('isValid');
            $filter->expects($this->once())->method('filter')->with('info')->willReturn($expectedPriority);
        }
        $serviceManager->get('ValidatorManager')->setService('Library\LogLevel', $validator);
        $serviceManager->get('FilterManager')->setService('Library\LogLevel', $filter);

        $logger = $this->createMock('Zend\Log\Logger');
        $logger->expects($this->once())->method('addWriter')->with(
            $this->callback(
                function ($writer) use ($expectedPriority) {
                    if (!$writer instanceof \Zend\Log\Writer\Stream) {
                        return false;
                    };
                    $stream = \PHPUnit_Framework_Assert::readAttribute($writer, 'stream');
                    if (!is_resource($stream) or get_resource_type($stream) != 'stream') {
                        return false;
                    }
                    if (stream_get_meta_data($stream)['uri'] != 'php://stderr') {
                        return false;
                    }

                    $filter = \PHPUnit_Framework_Assert::readAttribute($writer, 'filters')[0];
                    if (!$filter instanceof \Zend\Log\Filter\Priority) {
                        return false;
                    }
                    $priority = \PHPUnit_Framework_Assert::readAttribute($filter, 'priority');
                    if ($priority !== $expectedPriority) {
                        return false;
                    }
                    $operator = \PHPUnit_Framework_Assert::readAttribute($filter, 'operator');
                    if ($operator != '<=') {
                        return false;
                    }

                    return true;
                }
            )
        );

        $schemaManager = $this->createMock('Database\SchemaManager');
        $schemaManager->expects($this->once())->method('updateAll')->with($expectedPrune);

        $serviceManager->setService('Library\Logger', $logger);
        $serviceManager->setService('Database\SchemaManager', $schemaManager);

        $this->dispatch($cmdLine);

        $this->assertResponseStatusCode(0);
    }
}
