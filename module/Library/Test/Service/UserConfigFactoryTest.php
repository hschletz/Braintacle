<?php

/**
 * Tests for UserConfigFactory
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use ArrayAccess;
use ArrayObject;
use Braintacle\AppConfig;
use Laminas\ServiceManager\ServiceManager;
use Library\Service\UserConfigFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;

class UserConfigFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const ServiceName = 'Library\UserConfig';

    /**
     * Sample INI file content matching $iniContentParsed.
     */
    private $iniContentString = <<<'EOT'
[section1]
key1 = value1
key2 = value2
[section2]
key1 = value1a
EOT;

    /**
     * Sample parsed content matching $iniContentString
     */
    private $iniContentParsed = array(
        'section1' => [
            'key1' => 'value1',
            'key2' => 'value2',
        ],
        'section2' => [
            'key1' => 'value1a',
        ],
    );

    private function assertConfigProvided(ServiceManager $container): void
    {
        $factory = new UserConfigFactory();
        $config = $factory($container, static::ServiceName);
        $this->assertInstanceOf(ArrayAccess::class, $config);
        $this->assertEquals($this->iniContentParsed, $config->getArrayCopy());
    }

    public function testExistingConfig()
    {
        $container = new ServiceManager();
        $container->setService('ApplicationConfig', [static::ServiceName => new ArrayObject($this->iniContentParsed)]);

        $this->assertConfigProvided($container);
    }

    public function testConfigFromFile()
    {
        $root = vfsStream::setup('root');
        $fileName = vfsStream::newFile('test.ini')->withContent($this->iniContentString)->at($root)->url();

        $container = new ServiceManager();
        $container->setService('ApplicationConfig', [static::ServiceName => $fileName]);

        $this->assertConfigProvided($container);
    }

    public static function applicationConfigProvider()
    {
        return [
            [[]],
            [[static::ServiceName => '']],
        ];
    }

    #[DataProvider('applicationConfigProvider')]
    public function testConfigFromAppConfig(array $applicationConfig)
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('getAll')->willReturn($this->iniContentParsed);

        $container = new ServiceManager();
        $container->setService('ApplicationConfig', $applicationConfig);
        $container->setService(AppConfig::class, $appConfig);

        $this->assertConfigProvided($container);
    }
}
