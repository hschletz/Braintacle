<?php

/**
 * Tests for PackageBuilder
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

namespace Model\Test\Package;

use Database\Table\Packages;
use InvalidArgumentException;
use Laminas\Hydrator\HydratorInterface;
use Library\ArchiveManager;
use Mockery;
use Model\Package\Package;
use Model\Package\PackageBuilder;
use Model\Package\PackageManager;
use Model\Package\RuntimeException;
use Model\Package\Storage\Direct;
use Model\Package\Storage\StorageInterface;
use org\bovigo\vfs\vfsStream;
use ZipArchive;

class PackageBuilderTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    protected static $_tables = ['Packages'];

    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testBuildPackagePackageExists()
    {
        $packageManager = Mockery::mock(PackageManager::class);
        $packageManager->shouldNotReceive('deletePackage');

        $archiveManager = Mockery::mock(ArchiveManager::class);
        $storage = Mockery::mock(StorageInterface::class);
        $packagesTable = Mockery::mock(Packages::class);

        $model = Mockery::mock(
            PackageBuilder::class,
            [$packageManager, $archiveManager, $storage, $packagesTable]
        )->makePartial();
        $model->shouldReceive('checkName')->andThrow(new RuntimeException('package exists'));
        $model->shouldNotReceive('prepareStorage');
        $model->shouldNotReceive('autoArchive');
        $model->shouldNotReceive('writeToStorage');
        $model->shouldNotReceive('writeToDatabase');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("package exists");

        $model->buildPackage(['Name' => 'name'], false);
    }

    public function testBuildPackageWithoutFile()
    {
        $inputDataForMethod = [
            'Name' => 'name',
            'Platform' => 'platform',
            'FileLocation' => '',
        ];
        $InputDataForPrepareStorage = $inputDataForMethod + ['Id' => '42'];
        $inputDataForWriteToStorage = $InputDataForPrepareStorage + [
            'HashType' => 'hash_type',
            'Size' => 0,
            'Hash' => null,
        ];
        $inputDataForWriteToDatabase = $inputDataForWriteToStorage + ['NumFragments' => 0];

        $file = '';

        $packageManager = Mockery::mock(PackageManager::class);
        $packageManager->shouldNotReceive('deletePackage');

        $archiveManager = Mockery::mock(ArchiveManager::class);
        $storage = Mockery::mock(StorageInterface::class);
        $packagesTable = Mockery::mock(Packages::class);

        $model = Mockery::mock(
            PackageBuilder::class,
            [$packageManager, $archiveManager, $storage, $packagesTable]
        )->makePartial();
        $model->shouldReceive('checkName');
        $model->shouldReceive('generateId')->andReturn(42);
        $model->shouldReceive('prepareStorage')->with($InputDataForPrepareStorage)->andReturn('source');
        $model->shouldReceive('autoArchive')->with($InputDataForPrepareStorage, 'source', true)->andReturn($file);
        $model->shouldReceive('getHashType')->with('platform')->andReturn('hash_type');
        $model->shouldNotReceive('getFileHash');
        $model->shouldReceive('writeToStorage')->with($inputDataForWriteToStorage, $file, true)->andReturn(0);
        $model->shouldReceive('writeToDatabase')->once()->with($inputDataForWriteToDatabase);

        $model->buildPackage($inputDataForMethod, true);
    }

    public function testBuildPackageWithFile()
    {
        $inputDataForMethod = [
            'Name' => 'name',
            'Platform' => 'platform',
            'FileLocation' => '/location',
        ];
        $InputDataForPrepareStorage = $inputDataForMethod + ['Id' => '42'];
        $inputDataForWriteToStorage = $InputDataForPrepareStorage + [
            'HashType' => 'hash_type',
            'Size' => 7,
            'Hash' => 'hash',
        ];
        $inputDataForWriteToDatabase = $inputDataForWriteToStorage + ['NumFragments' => '5'];

        $file = vfsStream::newFile('archive')->withContent('content')->at(vfsStream::setup('root'))->url();

        $packageManager = Mockery::mock(PackageManager::class);
        $packageManager->shouldNotReceive('deletePackage');

        $archiveManager = Mockery::mock(ArchiveManager::class);
        $storage = Mockery::mock(StorageInterface::class);
        $packagesTable = Mockery::mock(Packages::class);

        $model = Mockery::mock(
            PackageBuilder::class,
            [$packageManager, $archiveManager, $storage, $packagesTable]
        )->makePartial();
        $model->shouldReceive('checkName');
        $model->shouldReceive('generateId')->andReturn(42);
        $model->shouldReceive('prepareStorage')->with($InputDataForPrepareStorage)->andReturn('source');
        $model->shouldReceive('autoArchive')->with($InputDataForPrepareStorage, 'source', true)->andReturn($file);
        $model->shouldReceive('getHashType')->with('platform')->andReturn('hash_type');
        $model->shouldReceive('getFileHash')->with($file, 'hash_type')->andReturn('hash');
        $model->shouldReceive('writeToStorage')->with($inputDataForWriteToStorage, $file, true)->andReturn(5);
        $model->shouldReceive('writeToDatabase')->once()->with($inputDataForWriteToDatabase);

        $model->buildPackage($inputDataForMethod, true);
    }

    public function testBuildPackageFileError()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No such file or directory:');

        $inputData = [
            'Name' => 'name',
            'Platform' => 'platform',
            'FileLocation' => '/location',
        ];
        $intermediateData = $inputData + ['Id' => '42'];

        $file = vfsStream::setup('root')->url() . '/invalid';

        $packageManager = Mockery::mock(PackageManager::class);
        $packageManager->shouldReceive('deletePackage')->once()->with('name');

        $archiveManager = Mockery::mock(ArchiveManager::class);
        $storage = Mockery::mock(StorageInterface::class);
        $packagesTable = Mockery::mock(Packages::class);

        $model = Mockery::mock(
            PackageBuilder::class,
            [$packageManager, $archiveManager, $storage, $packagesTable]
        )->makePartial();
        $model->shouldReceive('checkName');
        $model->shouldReceive('generateId')->andReturn(42);
        $model->shouldReceive('prepareStorage')->with($intermediateData)->andReturn('source');
        $model->shouldReceive('autoArchive')->with($intermediateData, 'source', true)->andReturn($file);
        $model->shouldReceive('getHashType');
        $model->shouldNotReceive('getFileHash');
        $model->shouldNotReceive('writeToStorage');
        $model->shouldNotReceive('writeToDatabase');

        $model->buildPackage($inputData, true);
    }

    public function testBuildPackageDeleteError()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('original message');

        $inputData = ['Name' => 'name'];

        $exception = new RuntimeException('original message');
        $exception2 = new \RuntimeException('delete failed');

        $packageManager = Mockery::mock(PackageManager::class);
        $packageManager->shouldReceive('deletePackage')->once()->with('name')->andThrow($exception2);

        $archiveManager = Mockery::mock(ArchiveManager::class);
        $storage = Mockery::mock(StorageInterface::class);
        $packagesTable = Mockery::mock(Packages::class);

        $model = Mockery::mock(
            PackageBuilder::class,
            [$packageManager, $archiveManager, $storage, $packagesTable]
        )->makePartial();
        $model->shouldReceive('checkName');
        $model->shouldReceive('generateId')->andThrow($exception);

        $model->buildPackage($inputData, true);
    }

    public function testCheckNameOk()
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->once())->method('packageExists')->with('name')->willReturn(false);

        $model = new PackageBuilder(
            $packageManager,
            $this->createStub(ArchiveManager::class),
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        $model->checkName('name');
    }

    public function testCheckNameException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectDeprecationMessage("Package 'name' already exists");

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('packageExists')->with('name')->willReturn(true);

        $model = new PackageBuilder(
            $packageManager,
            $this->createStub(ArchiveManager::class),
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        $model->checkName('name');
    }

    public function testPrepareStorage()
    {
        $data = ['Name' => 'name'];

        $storage = $this->createMock(StorageInterface::class);
        $storage->method('prepare')->with($data)->willReturn('result');

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $this->createStub(ArchiveManager::class),
            $storage,
            $this->createStub(Packages::class)
        );
        $this->assertEquals('result', $model->prepareStorage($data));
    }

    public function testGenerateId()
    {
        $model = $this->createPartialMock(PackageBuilder::class, []);
        $this->assertEqualsWithDelta(time(), $model->generateId(), 1);
    }

    public function testGetHashTypeWindows()
    {
        $model = $this->createPartialMock(PackageBuilder::class, []);
        $this->assertEquals('SHA256', $model->getHashType('windows'));
    }

    public function testGetHashTypeOther()
    {
        $model = $this->createPartialMock(PackageBuilder::class, []);
        $this->assertEquals('SHA1', $model->getHashType('unix'));
    }

    public function testGetFileHashSuccess()
    {
        $file = vfsStream::newFile('archive')->withContent('content')->at(vfsStream::setup('root'))->url();

        $model = $this->createPartialMock(PackageBuilder::class, []);
        $this->assertEquals(sha1('content'), $model->getFileHash($file, 'SHA1'));
    }

    public function testGetFileHashError()
    {
        $file = vfsStream::setup('root')->url() . '/invalid';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not compute SHA1 hash of '$file'");

        $model = $this->createPartialMock(PackageBuilder::class, []);
        $model->getFileHash($file, 'SHA1');
    }

    public function writeToStorageProvider()
    {
        return [
            ['filename', false, false],
            ['filename', true, true],
            ['archive', false, true],
            ['archive', true, true],
        ];
    }

    /** @dataProvider writeToStorageProvider */
    public function testWriteToStorage($file, $deleteSource, $deleteSourceForWrite)
    {
        $data = ['FileLocation' => 'filename'];

        $storage = $this->createMock(Direct::class);
        $storage->method('write')->with($data, $file, $deleteSourceForWrite)->willReturn(42);

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $this->createStub(ArchiveManager::class),
            $storage,
            $this->createStub(Packages::class)
        );
        $this->assertEquals(42, $model->writeToStorage($data, $file, $deleteSource));
    }

    public function testWriteToDatabase()
    {
        $data = ['Platform' => 'unix'];
        $extractedData = ['osname' => 'unix'];

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')->with(new Package($data))->willReturn($extractedData);

        $packagesTable = $this->createMock(Packages::class);
        $packagesTable->method('getHydrator')->willReturn($hydrator);
        $packagesTable->expects($this->once())->method('insert')->with($extractedData);

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $this->createStub(ArchiveManager::class),
            $this->createStub(StorageInterface::class),
            $packagesTable
        );
        $model->writeToDatabase($data);
    }

    public function testWriteToDatabaseInvalidPlatform()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid platform: nix');

        $data = ['Platform' => 'nix'];
        $extractedData = ['osname' => null];

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')->with(new Package($data))->willReturn($extractedData);

        $packagesTable = $this->createMock(Packages::class);
        $packagesTable->method('getHydrator')->willReturn($hydrator);
        $packagesTable->expects($this->never())->method('insert');

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $this->createStub(ArchiveManager::class),
            $this->createStub(StorageInterface::class),
            $packagesTable
        );
        $model->writeToDatabase($data);
    }

    public function testAutoArchiveWindowsCreateArchiveKeepSource()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $archiveObject = $this->createStub(ZipArchive::class);

        $data = ['FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows'];

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(ArchiveManager::ZIP, 'path/archive')
                       ->willReturn($archiveObject);
        $archiveManager->expects($this->once())
                       ->method('addFile')
                       ->with($archiveObject, $source, 'FileName');
        $archiveManager->expects($this->once())
                       ->method('closeArchive')
                       ->with($archiveObject, false);

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        $this->assertEquals('path/archive', $model->autoArchive($data, 'path', false));
        $this->assertFileExists($source);
    }

    public function testAutoArchiveWindowsCreateArchiveDeleteSource()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = ['FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows'];
        $archiveObject = $this->createStub(ZipArchive::class);

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(ArchiveManager::ZIP, 'path/archive')
                       ->willReturn($archiveObject);
        $archiveManager->expects($this->once())
                       ->method('addFile')
                       ->with($archiveObject, $source, 'FileName');
        $archiveManager->expects($this->once())
                       ->method('closeArchive')
                       ->with($archiveObject, false);

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        $this->assertEquals('path/archive', $model->autoArchive($data, 'path', true));
        $this->assertFileDoesNotExist($source);
    }

    public function testAutoArchiveWindowsErrorOnArchiveCreation()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = ['FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows'];

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(ArchiveManager::ZIP, 'path/archive')
                       ->will($this->throwException(new RuntimeException('createArchive')));
        $archiveManager->expects($this->never())->method('addFile');
        $archiveManager->expects($this->never())->method('closeArchive');

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        try {
            $model->autoArchive($data, 'path', true);
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('createArchive', $e->getMessage());
            $this->assertFileExists($source);
        }
    }

    public function testAutoArchiveWindowsErrorAfterArchiveCreation()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $archivePath = $root->url() . '/archive';
        $archiveObject = $this->createStub(ZipArchive::class);
        $data = ['FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows'];

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(ArchiveManager::ZIP, $archivePath)
                       ->willReturnCallback(function () use ($root, $archiveObject) {
                           vfsStream::newFile('archive')->at($root)->url();
                           return $archiveObject;
                       });
        $archiveManager->expects($this->once())
                       ->method('addFile')
                       ->with($archiveObject, $source, 'FileName')
                       ->will($this->throwException(new RuntimeException('closeArchive')));
        $archiveManager->expects($this->once())
                       ->method('closeArchive')
                       ->with($archiveObject, true);

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        try {
            $model->autoArchive($data, $root->url(), true);
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('closeArchive', $e->getMessage());
            $this->assertFileExists($source);
            $this->assertFileDoesNotExist($archivePath);
        }
    }

    public function testAutoArchiveWindowsAlreadyArchive()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = ['FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows'];

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(ArchiveManager::ZIP, $source)
                       ->willReturn(true);
        $archiveManager->expects($this->never())->method('createArchive');
        $archiveManager->expects($this->never())->method('addFile');
        $archiveManager->expects($this->never())->method('closeArchive');

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        $this->assertEquals($source, $model->autoArchive($data, 'path', true));
        $this->assertFileExists($source);
    }

    public function testAutoArchiveWindowsArchiveNotSupported()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = ['FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows'];

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->once())->method('isSupported')->with(ArchiveManager::ZIP)->willReturn(false);
        $archiveManager->expects($this->never())->method('isArchive');
        $archiveManager->expects($this->never())->method('createArchive');
        $archiveManager->expects($this->never())->method('addFile');
        $archiveManager->expects($this->never())->method('closeArchive');

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );

        // PHPUnit's error handler would throw an exception when the notice is
        // triggered, and the tested method would not complete. Temporarily
        // override the error handler for this test.
        $message = null;
        set_error_handler(
            function ($errno, $errstr, $errfile = null, $errline = null, $errcontext = null) use (&$message): ?bool {
                $message = $errstr;
                return true;
            },
            E_USER_NOTICE
        );
        try {
            $result = $model->autoArchive($data, 'path', true);
        } finally {
            restore_error_handler();
        }

        $this->assertEquals($source, $result);
        $this->assertFileExists($source);
        $this->assertEquals("Support for archive type 'zip' not available. Assuming archive.", $message);
    }

    public function testAutoArchiveUnsupportedPlatform()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = ['FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'linux'];

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->never())->method('isSupported');
        $archiveManager->expects($this->never())->method('isArchive');
        $archiveManager->expects($this->never())->method('createArchive');
        $archiveManager->expects($this->never())->method('addFile');
        $archiveManager->expects($this->never())->method('closeArchive');

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        $this->assertEquals($source, $model->autoArchive($data, 'path', true));
        $this->assertFileExists($source);
    }

    public function testAutoArchiveNoSourceFile()
    {
        $source = '';
        $data = ['FileLocation' => $source];

        $archiveManager = $this->createMock(ArchiveManager::class);
        $archiveManager->expects($this->never())->method('isSupported');
        $archiveManager->expects($this->never())->method('isArchive');
        $archiveManager->expects($this->never())->method('createArchive');
        $archiveManager->expects($this->never())->method('addFile');
        $archiveManager->expects($this->never())->method('closeArchive');

        $model = new PackageBuilder(
            $this->createStub(PackageManager::class),
            $archiveManager,
            $this->createStub(StorageInterface::class),
            $this->createStub(Packages::class)
        );
        $this->assertSame($source, $model->autoArchive($data, 'path', true));
    }
}
