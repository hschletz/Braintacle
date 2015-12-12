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

namespace DatabaseManager\Test;

use Zend\Log\Logger;

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
            array('--loglevel'),
            array('--loglevel='),
            array('--loglevel=inf'),
            array('--loglevel=infos'),
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

    public function schemaManagerActionProvider()
    {
        return array(
            array('', Logger::INFO),
            array('--loglevel=emerg', Logger::EMERG),
            array('--loglevel=alert', Logger::ALERT),
            array('--loglevel=crit', Logger::CRIT),
            array('--loglevel=err', Logger::ERR),
            array('--loglevel=warn', Logger::WARN),
            array('--loglevel=notice', Logger::NOTICE),
            array('--loglevel=info', Logger::INFO),
            array('--loglevel=debug', Logger::DEBUG),
        );
    }

    /**
     * @dataProvider schemaManagerActionProvider
     */
    public function testSchemaManagerAction($cmdLine, $expectedPriority)
    {
        $logger = $this->getMock('Zend\Log\Logger');
        $logger->expects($this->once())->method('addWriter')->with(
            $this->callback(
                function ($writer) use ($expectedPriority) {
                    if (!$writer instanceof \Zend\Log\Writer\Stream) {
                        return false;
                    };
                    $stream = new \ReflectionProperty($writer, 'stream');
                    $stream->setAccessible(true);
                    $stream = $stream->getValue($writer);
                    if (!is_resource($stream) or get_resource_type($stream) != 'stream') {
                        return false;
                    }
                    if (stream_get_meta_data($stream)['uri'] != 'php://stderr') {
                        return false;
                    }

                    $filters = new \ReflectionProperty($writer, 'filters');
                    $filters->setAccessible(true);
                    $filter = $filters->getValue($writer)[0];
                    if (!$filter instanceof \Zend\Log\Filter\Priority) {
                        return false;
                    }
                    $priority = new \ReflectionProperty($filter, 'priority');
                    $priority->setAccessible(true);
                    if ($priority->getValue($filter) !== $expectedPriority) {
                        return false;
                    }
                    $operator = new \ReflectionProperty($filter, 'operator');
                    $operator->setAccessible(true);
                    if ($operator->getValue($filter) != '<=') {
                        return false;
                    }

                    return true;
                }
            )
        );

        $schemaManager = $this->getMockBuilder('Database\SchemaManager')->disableOriginalConstructor()->getMock();
        $schemaManager->expects($this->once())->method('updateAll');

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Library\Logger', $logger)
             ->setService('Database\SchemaManager', $schemaManager);

        $this->dispatch($cmdLine);

        $this->assertResponseStatusCode(0);
    }
}
