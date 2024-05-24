<?php

namespace Braintacle\Test;

use Braintacle\AppConfig;
use InvalidArgumentException;
use Laminas\Config\Reader\ReaderInterface;
use Library\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AppConfigTest extends TestCase
{
    public static function fileNameProvider()
    {
        $default = Application::getPath('config/braintacle.ini');

        return [
            [null, $default],
            ['', $default],
            ['/path', '/path'],
        ];
    }

    #[DataProvider('fileNameProvider')]
    public function testFileName(?string $input, string $fileName)
    {
        $reader = $this->createMock(ReaderInterface::class);
        $reader->expects($this->once())->method('fromFile')->with($fileName)->willReturn([]);

        new AppConfig($reader, $input);
    }

    public function testFullConfig()
    {
        $config = [
            'debug' => [
                'display backtrace' => true,
            ],
        ];

        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn($config);

        $appConfig = new AppConfig($reader, null);
        $this->assertEquals($config, $appConfig->getAll());
        $this->assertTrue($appConfig->debug['display backtrace']);
    }

    public function testEmptyConfig()
    {
        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn([]);

        $appConfig = new AppConfig($reader, null);
        $this->assertEquals([], $appConfig->getAll());
        $this->assertEquals([], $appConfig->debug);
    }

    public function testInvalidKey()
    {
        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn([]);

        $appConfig = new AppConfig($reader, null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid config key: invalid');
        $appConfig->invalid;
    }
}
