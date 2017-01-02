<?php
/**
 * Tests for Build controller
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

use Zend\Log\Logger;

class BuildTest extends AbstractControllerTest
{
    /**
     * Config mock
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Package manager mock
     * @var \Model\Package\PackageManager
     */
    protected $_packageManager;

    public function setUp()
    {
        parent::setUp();

        $this->_config = $this->createMock('Model\Config');
        static::$serviceManager->setService('Model\Config', $this->_config);

        $this->_packageManager = $this->createMock('Model\Package\PackageManager');
        static::$serviceManager->setService('Model\Package\PackageManager', $this->_packageManager);
    }

    public function testSuccess()
    {
        $this->_config->method('__get')->willReturnArgument(0);

        $this->_packageManager->expects($this->once())->method('buildPackage')->with(
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

        $this->_route->method('getMatchedParam')->willReturnMap(
            array(
                array('name', null, 'packageName'),
                array('file', null, 'path/fileName'),
            )
        );
        $this->_console->expects($this->once())->method('writeLine')->with('Package successfully built.');

        $this->assertEquals(0, $this->_dispatch());
    }
}
