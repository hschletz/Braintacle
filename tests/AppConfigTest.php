<?php

namespace Braintacle\Test;

use Braintacle\AppConfig;
use Laminas\Config\Reader\ReaderInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use UnhandledMatchError;

class AppConfigTest extends TestCase
{
    public function testGetAllWithConstructorFile()
    {
        $reader = $this->createMock(ReaderInterface::class);
        $reader->method('fromFile')->with('file')->willReturn(['config']);

        $appConfig = new AppConfig($reader, 'file');
        $this->assertEquals(['config'], $appConfig->getAll());
    }

    public function testGetAllWithOverriddenFile()
    {
        $reader = $this->createMock(ReaderInterface::class);
        $reader->method('fromFile')->with('file')->willReturn(['config']);

        $appConfig = new AppConfig($reader, '');
        $appConfig->setFile('file');

        $this->assertEquals(['config'], $appConfig->getAll());
    }

    public function testSetFileWillThrowWithLoadedConfig()
    {
        $reader = $this->createMock(ReaderInterface::class);
        $reader->method('fromFile')->with('file')->willReturn(['config']);

        $appConfig = new AppConfig($reader, 'file');
        $appConfig->getAll();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot set config file. Config is already loaded.');
        $appConfig->setFile('file2');
    }

    public function testEmptyConfig()
    {
        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn([]);

        $appConfig = new AppConfig($reader, '/file');
        $this->assertEquals([], $appConfig->getAll());
        $this->assertEquals([], $appConfig->debug);
    }

    public function testValidSections()
    {
        $config = [
            'database' => [
                'databaseOption' => 'databaseValue',
            ],
            'debug' => [
                'debugOption' => 'debugValue',
            ],
        ];

        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn($config);

        $appConfig = new AppConfig($reader, 'file');
        $this->assertEquals(['databaseOption' => 'databaseValue'], $appConfig->database);
        $this->assertEquals(['debugOption' => 'debugValue'], $appConfig->debug);
    }

    public function testInvalidKey()
    {
        $reader = $this->createStub(ReaderInterface::class);
        $reader->method('fromFile')->willReturn([]);

        $appConfig = new AppConfig($reader, '/file');

        $this->expectException(UnhandledMatchError::class);
        $appConfig->invalid;
    }
}
