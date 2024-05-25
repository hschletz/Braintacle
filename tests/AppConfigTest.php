<?php

namespace Braintacle\Test;

use Braintacle\AppConfig;
use InvalidArgumentException;
use Laminas\Config\Reader\ReaderInterface;
use PHPUnit\Framework\TestCase;

class AppConfigTest extends TestCase
{
    public function testFileName()
    {
        $reader = $this->createMock(ReaderInterface::class);
        $reader->expects($this->once())->method('fromFile')->with('/file')->willReturn([]);

        new AppConfig($reader, '/file');
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

        $appConfig = new AppConfig($reader, '/file');
        $this->assertEquals($config, $appConfig->getAll());
        $this->assertTrue($appConfig->debug['display backtrace']);
    }

    public function testEmptyConfig()
    {
        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn([]);

        $appConfig = new AppConfig($reader, '/file');
        $this->assertEquals([], $appConfig->getAll());
        $this->assertEquals([], $appConfig->debug);
    }

    public function testInvalidKey()
    {
        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn([]);

        $appConfig = new AppConfig($reader, '/file');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid config key: invalid');
        $appConfig->invalid;
    }
}
