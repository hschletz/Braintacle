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
        $this->setApplicationConfig(\Library\Application::getApplicationConfig('PackageBuilder', true));
    }

    public function invalidRouteProvider()
    {
        return array(
            array(''),
            array('name'),
            array('name file extra'),
            array('--flag name file'),
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

    public function testPackageBuilderAction()
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('__get')->willReturnArgument(0);

        $packageManager = $this->getMockBuilder('Model\Package\PackageManager')
                               ->disableOriginalConstructor()
                               ->getMock();
        $packageManager->expects($this->once())->method('buildPackage')->with(
            array(
                'Name' => 'packageName',
                'Comment' => null,
                'FileName' => 'fileName',
                'FileLocation' => 'path/fileName',
                'Priority' => 'defaultPackagePriority',
                'Platform' => 'defaultPlatform',
                'DeployAction' => 'defaultAction',
                'ActionParam' => 'defaultActionParam',
                'Warn' => 'defaultWarn',
                'WarnMessage' => 'defaultWarnMessage',
                'WarnCountdown' => 'defaultWarnCountdown',
                'WarnAllowAbort' => 'defaultWarnAllowAbort',
                'WarnAllowDelay' => 'defaultWarnAllowDelay',
                'PostInstMessage' => 'defaultPostInstMessage',
                'MaxFragmentSize' => 'defaultMaxFragmentSize',
            ),
            false
        );

        $console = $this->getMockBuilder('Zend\Console\Adapter\AbstractAdapter')
                        ->setMethods(array('writeLine'))
                        ->getMockForAbstractClass();
        $console->expects($this->once())->method('writeLine')->with('Package successfully built.');

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Model\Config', $config)
             ->setService('Model\Package\packageManager', $packageManager)
             ->setService('console', $console);

        $this->dispatch('packageName path/fileName');

        $this->assertResponseStatusCode(0);
    }
}
