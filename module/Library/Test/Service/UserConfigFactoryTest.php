<?php

/**
 * Tests for UserConfigFactory
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Test\Service;

use ArrayObject;
use Laminas\ServiceManager\ServiceManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;

class UserConfigFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Per-test factory instance
     * @var \Library\Service\UserConfigFactory
     */
    protected $_factory;

    /**
     * Per-Test container instance
     * @var MockObject|ServiceManager
     */
    protected $_container;

    /**
     * Backup of the BRAINTACLE_CONFIG envirinment variable
     * @var mixed
     */
    protected $_envBackup;

    /**
     * Sample INI file content matching $_iniContentParsed
     * @var string
     */
    protected $_iniContentString = <<<'EOT'
[section1]
key1 = value1
key2 = value2
[section2]
key1 = value1a
EOT;

    /**
     * Sample parsed content matching $_iniContentString
     * @var array
     */
    protected $_iniContentParsed = array(
        'section1' => array(
            'key1' => 'value1',
            'key2' => 'value2',
        ),
        'section2' => array(
            'key1' => 'value1a',
        ),
    );

    public function setUp(): void
    {
        $this->_envBackup = getenv('BRAINTACLE_CONFIG');

        $this->_factory = new \Library\Service\UserConfigFactory();
        $this->_container = $this->createMock('Laminas\ServiceManager\ServiceManager');
    }

    public function tearDown(): void
    {
        if ($this->_envBackup === false) {
            putenv('BRAINTACLE_CONFIG');
        } else {
            putenv('BRAINTACLE_CONFIG=' . $this->_envBackup);
        }
    }

    private function getFromFactory()
    {
        return ($this->_factory)($this->_container, 'foo');
    }

    public function testFilenameFromEnvironment()
    {
        $root = vfsStream::setup('root');
        $filename = vfsStream::newFile('test.ini')->withContent($this->_iniContentString)->at($root)->url();
        putenv('BRAINTACLE_CONFIG=' . $filename);

        $this->_container->expects($this->once())
                         ->method('get')
                         ->with('ApplicationConfig')
                         ->willReturn(array());

        $this->assertEquals($this->_iniContentParsed, $this->getFromFactory()->getArrayCopy());
    }

    public function testFilenameFromApplicationConfig()
    {
        $root = vfsStream::setup('root');
        $filename = vfsStream::newFile('test.ini')->withContent($this->_iniContentString)->at($root)->url();
        putenv('BRAINTACLE_CONFIG=ignored');

        $this->_container->expects($this->once())
                         ->method('get')
                         ->with('ApplicationConfig')
                         ->willReturn(array('Library\UserConfig' => $filename));

        $this->assertEquals($this->_iniContentParsed, $this->getFromFactory()->getArrayCopy());
    }

    public function testArrayFromApplicationConfig()
    {
        putenv('BRAINTACLE_CONFIG=ignored');

        $this->_container->expects($this->once())
                         ->method('get')
                         ->with('ApplicationConfig')
                         ->willReturn(['Library\UserConfig' => new ArrayObject($this->_iniContentParsed)]);

        $this->assertEquals($this->_iniContentParsed, $this->getFromFactory()->getArrayCopy());
    }

    public function testFallbackWhenEnvironmentEmpty()
    {
        putenv('BRAINTACLE_CONFIG=');

        $this->_container->expects($this->once())
                         ->method('get')
                         ->with('ApplicationConfig')
                         ->willReturn(array());

        $reader = new \Laminas\Config\Reader\Ini();
        $iniContentParsed = $reader->fromFile(\Library\Application::getPath('config/braintacle.ini'));

        $this->assertEquals($iniContentParsed, $this->getFromFactory()->getArrayCopy());
    }

    public function testFallbackWhenEnvironmentNotSet()
    {
        putenv('BRAINTACLE_CONFIG');

        $this->_container->expects($this->once())
                         ->method('get')
                         ->with('ApplicationConfig')
                         ->willReturn(array());

        $reader = new \Laminas\Config\Reader\Ini();
        $iniContentParsed = $reader->fromFile(\Library\Application::getPath('config/braintacle.ini'));

        $this->assertEquals($iniContentParsed, $this->getFromFactory()->getArrayCopy());
    }
}
