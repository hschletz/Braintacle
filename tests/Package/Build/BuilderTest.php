<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Package\Action;
use Braintacle\Package\Assignments;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Build\SourceFile;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use Braintacle\Package\Platform;
use Exception;
use Model\Package\Package as LegacyPackage;
use Model\Package\PackageBuilder;
use Model\Package\PackageManager;
use Model\Package\RuntimeException as PackageRuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Builder::class)]
#[UsesClass(SourceFile::class)]
final class BuilderTest extends TestCase
{
    public static function deleteSourceProvider()
    {
        return [[true], [false]];
    }

    private function createBuilderMock(
        array $methods,
        ?PackageManager $packageManager = null,
        ?PackageBuilder $packageBuilder = null,
        ?Assignments $assignments = null,
    ): MockObject|Builder {
        return $this->getMockBuilder(Builder::class)->setConstructorArgs([
            $packageManager ?? $this->createStub(PackageManager::class),
            $packageBuilder ?? $this->createStub(PackageBuilder::class),
            $assignments ?? $this->createStub(Assignments::class),
        ])->onlyMethods($methods)->getMock();
    }

    public function testBuildExceptionCleanup()
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->once())->method('deletePackage');

        $builder = $this->createBuilderMock(
            [],
            packageManager: $packageManager,
        );

        $package = $this->createStub(Package::class);
        $package->name = 'package name';
        $package->method('toArray')->willThrowException(new Exception('test'));

        $this->expectException(PackageRuntimeException::class);
        $this->expectExceptionMessage('test');
        $builder->build($package, null, false);
    }

    public function testBuildExceptionCleanupIgnoresDeleteError()
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager
            ->expects($this->once())
            ->method('deletePackage')
            ->willThrowException(new Exception('delete failed'));

        $builder = $this->createBuilderMock(
            [],
            packageManager: $packageManager,
        );

        $package = $this->createStub(Package::class);
        $package->name = 'package name';
        $package->method('toArray')->willThrowException(new Exception('test'));

        $this->expectException(PackageRuntimeException::class);
        $this->expectExceptionMessage('test');
        $builder->build($package, null, false);
    }

    public function testBuildPackageExists()
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->never())->method('deletePackage');

        $packageBuilder = $this->createMock(PackageBuilder::class);
        $packageBuilder->method('checkName')->with('package name')->willThrowException(new Exception('package exists'));

        $builder = $this->createBuilderMock(
            ['prepareFile', 'write'],
            packageManager: $packageManager,
            packageBuilder: $packageBuilder,
        );
        $builder->expects($this->never())->method('prepareFile');
        $builder->expects($this->never())->method('write');

        $package = $this->createStub(Package::class);
        $package->name = 'package name';

        $this->expectExceptionMessage('package exists');
        $builder->build($package, null, false);
    }

    #[TestWith([Action::Store])]
    #[TestWith([Action::Launch])]
    public function testBuildNoFileException(Action $action)
    {
        $packageBuilder = $this->createMock(PackageBuilder::class);

        $builder = $this->createBuilderMock(
            ['prepareFile', 'write'],
            packageBuilder: $packageBuilder,
        );
        $builder->expects($this->never())->method('prepareFile');
        $builder->expects($this->never())->method('write');

        $package = $this->createStub(Package::class);
        $package->method('toArray')->willReturn([]);
        $package->name = 'name';
        $package->platform = Platform::Linux;
        $package->action = $action;

        $this->expectException(PackageRuntimeException::class);
        $this->expectExceptionMessage('Missing file');

        $builder->build($package, null, true);
    }

    #[DataProvider('deleteSourceProvider')]
    public function testBuildNoFileExecute(bool $deleteSource)
    {
        $packageBuilder = $this->createMock(PackageBuilder::class);
        $packageBuilder->method('generateId')->willReturn(42);
        $packageBuilder->method('getHashType')->with('linux')->willReturn('hash_type');

        $builder = $this->createBuilderMock(
            ['prepareFile', 'write'],
            packageBuilder: $packageBuilder,
        );
        $builder->expects($this->never())->method('prepareFile');
        $builder->expects($this->once())->method('write')->with([
            'foo' => 'bar',
            'Id' => 42,
            'HashType' => 'hash_type',
            'FileLocation' => '',
            'Size' => 0,
            'Hash' => null,
        ], $deleteSource);

        $package = $this->createStub(Package::class);
        $package->method('toArray')->willReturn(['foo' => 'bar']);
        $package->name = 'name';
        $package->platform = Platform::Linux;
        $package->action = Action::Execute;

        $builder->build($package, null, $deleteSource);
    }

    #[DataProvider('deleteSourceProvider')]
    public function testBuildFile(bool $deleteSource)
    {
        $sourceFile = $this->createStub(SourceFile::class);

        $packageBuilder = $this->createMock(PackageBuilder::class);
        $packageBuilder->method('generateId')->willReturn(42);
        $packageBuilder->method('getHashType')->with('linux')->willReturn('hash_type');

        $builder = $this->createBuilderMock(
            ['prepareFile', 'write'],
            packageBuilder: $packageBuilder,
        );
        $builder->method('prepareFile')->with(
            [
                'foo' => 'bar',
                'Id' => 42,
                'HashType' => 'hash_type',
            ],
            $sourceFile,
            $deleteSource,
        )->willReturn(['prepared' => true]);
        $builder->expects($this->once())->method('write')->with(['prepared' => true], $deleteSource);

        $package = $this->createStub(Package::class);
        $package->method('toArray')->willReturn(['foo' => 'bar']);
        $package->name = 'name';
        $package->platform = Platform::Linux;
        $package->action = Action::Execute;

        $builder->build($package, $sourceFile, $deleteSource);
    }

    public function testUpdate()
    {
        $packageUpdate = new PackageUpdate();
        $packageUpdate->name = 'new_name';
        $packageUpdate->deployPending = true;
        $packageUpdate->deployRunning = false;
        $packageUpdate->deploySuccess = true;
        $packageUpdate->deployError = false;
        $packageUpdate->deployGroups = true;

        $oldPackage = new LegacyPackage();
        $oldPackage->id = 1;
        $oldPackage->name = 'old_name';

        $newPackage = new LegacyPackage();
        $newPackage->id = 2;

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getPackage')->willReturnMap([
            ['old_name', $oldPackage],
            ['new_name', $newPackage],
        ]);
        $packageManager->expects($this->once())->method('deletePackage')->with('old_name');

        $assignments = $this->createMock(Assignments::class);
        $assignments->expects($this->once())->method('updateAssignments')->with(1, 2, true, false, true, false, true);

        $sourceFile = $this->createStub(SourceFile::class);

        $builder = $this->createBuilderMock(
            ['build'],
            packageManager: $packageManager,
            assignments: $assignments,
        );
        $builder->expects($this->once())->method('build')->with($packageUpdate, $sourceFile, true);

        $builder->update($packageUpdate, $sourceFile, 'old_name');
    }

    #[DataProvider('deleteSourceProvider')]
    public function testPrepareFile(bool $deleteSource)
    {
        $archive = vfsStream::newFile('archive')->withContent('content')->at(vfsStream::setup('root'))->url();

        $packageBuilder = $this->createMock(PackageBuilder::class);
        $packageBuilder->method('prepareStorage')->with([
            'HashType' => 'hash_type',
            'FileName' => 'file.zip',
            'FileLocation' => '/tmp/file.zip',
        ])->willReturn('storagePrepared');
        $packageBuilder->method('autoArchive')->with(
            [
                'HashType' => 'hash_type',
                'FileName' => 'file.zip',
                'FileLocation' => '/tmp/file.zip',
            ],
            'storagePrepared',
            $deleteSource,
        )->willReturn($archive);
        $packageBuilder->method('getFileHash')->with($archive, 'hash_type')->willReturn('file_hash');

        $builder = $this->createBuilderMock([], packageBuilder: $packageBuilder);

        $sourceFile = new SourceFile('file.zip', '/tmp/file.zip');
        $this->assertEquals(
            [
                'HashType' => 'hash_type',
                'FileName' => 'file.zip',
                'FileLocation' => '/tmp/file.zip',
                'Archive' => $archive,
                'Size' => 7,
                'Hash' => 'file_hash',
            ],
            $builder->prepareFile(['HashType' => 'hash_type'], $sourceFile, $deleteSource),
        );
    }

    #[DataProvider('deleteSourceProvider')]
    public function testWriteWithoutFile(bool $deleteSource)
    {
        $packageBuilder = $this->createMock(PackageBuilder::class);
        $packageBuilder->method('writeToStorage')->with(
            ['foo' => 'bar'],
            '',
            $deleteSource
        )->willReturn(42);
        $packageBuilder->expects($this->once())->method('writeToDatabase')->with([
            'foo' => 'bar',
            'NumFragments' => 42,
        ]);

        $builder = $this->createBuilderMock([], packageBuilder: $packageBuilder);
        $builder->write(['foo' => 'bar'], $deleteSource);
    }

    #[DataProvider('deleteSourceProvider')]
    public function testWriteWithFile(bool $deleteSource)
    {
        $packageBuilder = $this->createMock(PackageBuilder::class);
        $packageBuilder->method('writeToStorage')->with(
            ['Archive' => '_archive'],
            '_archive',
            $deleteSource
        )->willReturn(42);
        $packageBuilder->expects($this->once())->method('writeToDatabase')->with([
            'Archive' => '_archive',
            'NumFragments' => 42,
        ]);

        $builder = $this->createBuilderMock([], packageBuilder: $packageBuilder);
        $builder->write(['Archive' => '_archive'], $deleteSource);
    }
}
