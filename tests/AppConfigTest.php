<?php

namespace Braintacle\Test;

use Braintacle\AppConfig;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use UnhandledMatchError;

class AppConfigTest extends TestCase
{
    public function testGetAllWithConstructorFile()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->with('file')->willReturn("[section]\nkey=true");

        $appConfig = new AppConfig($filesystem, 'file');
        $this->assertEquals(['section' => ['key' => true]], $appConfig->getAll());
    }

    public function testGetAllWithOverriddenFile()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->with('file')->willReturn("[section]\nkey=true");

        $appConfig = new AppConfig($filesystem, '');
        $appConfig->setFile('file');

        $this->assertEquals(['section' => ['key' => true]], $appConfig->getAll());
    }

    public function testSetFileWillThrowWithLoadedConfig()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->with('file')->willReturn("[section]\nkey=true");

        $appConfig = new AppConfig($filesystem, 'file');
        $appConfig->getAll();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot set config file. Config is already loaded.');
        $appConfig->setFile('file2');
    }

    public function testEmptyConfig()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->with('file')->willReturn('');

        $appConfig = new AppConfig($filesystem, 'file');
        $this->assertEquals([], $appConfig->getAll());
        $this->assertEquals([], $appConfig->debug);
    }

    public function testValidSections()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->willReturn(
            "[database]\ndatabaseOption=databaseValue\n[debug]\ndebugOption=debugValue"
        );

        $appConfig = new AppConfig($filesystem, 'file');
        $this->assertEquals(['databaseOption' => 'databaseValue'], $appConfig->database);
        $this->assertEquals(['debugOption' => 'debugValue'], $appConfig->debug);
    }

    public function testInvalidKey()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->willReturn("[section]\nkey=true");

        $appConfig = new AppConfig($filesystem, 'file');

        $this->expectException(UnhandledMatchError::class);
        $appConfig->invalid;
    }

    public function testParseError()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->willReturn('=');

        $appConfig = new AppConfig($filesystem, 'file');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error parsing config file file');
        @$appConfig->getAll();
    }
}
