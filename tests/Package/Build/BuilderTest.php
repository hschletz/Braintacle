<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Package\Action;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use LogicException;
use Model\Package\Package as LegacyPackage;
use Model\Package\PackageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Builder::class)]
final class BuilderTest extends TestCase
{
    private function createBuilder(
        ?PackageManager $packageManager = null,
        ?Filesystem $filesystem = null,
    ): Builder {
        return new Builder(
            $packageManager ?? $this->createStub(PackageManager::class),
            $filesystem ?? $this->createStub(Filesystem::class),
        );
    }

    public static function noFileExceptionProvider()
    {
        return [
            [Action::Store],
            [Action::Launch],
        ];
    }

    #[DataProvider('noFileExceptionProvider')]
    public function testBuildNoFileException(Action $action)
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->never())->method('buildPackage');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method('tempnam');

        $builder = $this->createBuilder(
            packageManager: $packageManager,
            filesystem: $filesystem,
        );

        $package = $this->createStub(Package::class);
        $package->method('toArray')->willReturn([]);
        $package->action = $action;

        $uploadedFile = $this->createStub(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_NO_FILE);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Missing file');

        $builder->build($package, $uploadedFile);
    }

    public function testBuildNoFileExecute()
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->once())->method('buildPackage')->with(
            [
                'foo' => 'bar',
                'FileLocation' => '',
            ],
            true,
        );

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method('tempnam');

        $builder = $this->createBuilder(
            packageManager: $packageManager,
            filesystem: $filesystem,
        );

        $package = $this->createStub(Package::class);
        $package->method('toArray')->willReturn(['foo' => 'bar']);
        $package->action = Action::Execute;

        $uploadedFile = $this->createStub(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_NO_FILE);

        $builder->build($package, $uploadedFile);
    }

    public function testBuildFile()
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->once())->method('buildPackage')->with(
            [
                'foo' => 'bar',
                'FileName' => 'file_name',
                'FileLocation' => 'temp_file',
            ],
            true,
        );

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('tempnam')
            ->with(ini_get('upload_tmp_dir'), 'braintacle_upload_')
            ->willReturn('temp_file');

        $builder = $this->createBuilder(
            packageManager: $packageManager,
            filesystem: $filesystem,
        );

        $package = $this->createStub(Package::class);
        $package->method('toArray')->willReturn(['foo' => 'bar']);
        $package->action = Action::Execute;

        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_OK);
        $uploadedFile->method('getClientFilename')->willReturn('file_name');
        $uploadedFile->expects($this->once())->method('moveTo')->with('temp_file');

        $builder->build($package, $uploadedFile);
    }

    #[DataProvider('noFileExceptionProvider')]
    public function testUpdateNoFileException(Action $action)
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->never())->method('updatePackage');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method('tempnam');

        $builder = $this->createBuilder(
            packageManager: $packageManager,
            filesystem: $filesystem,
        );

        $package = $this->createStub(PackageUpdate::class);
        $package->method('toArray')->willReturn([]);
        $package->action = $action;

        $uploadedFile = $this->createStub(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_NO_FILE);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Missing file');

        $builder->update($package, $uploadedFile, 'old_package');
    }

    public function testUpdateNoFileExecute()
    {
        $oldPackage = $this->createStub(LegacyPackage::class);

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getPackage')->with('old_package')->willReturn($oldPackage);

        $packageManager->expects($this->once())->method('updatePackage')->with(
            $oldPackage,
            [
                'foo' => 'bar',
                'FileLocation' => '',
            ],
            true,
            false,
            true,
            false,
            true,
            false,
        );

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method('tempnam');

        $builder = $this->createBuilder(
            packageManager: $packageManager,
            filesystem: $filesystem,
        );

        $package = $this->createStub(PackageUpdate::class);
        $package->method('toArray')->willReturn(['foo' => 'bar']);
        $package->action = Action::Execute;
        $package->deployPending = false;
        $package->deployRunning = true;
        $package->deploySuccess = false;
        $package->deployError = true;
        $package->deployGroups = false;

        $uploadedFile = $this->createStub(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_NO_FILE);

        $builder->update($package, $uploadedFile, 'old_package');
    }

    public function testUpdateFile()
    {
        $oldPackage = $this->createStub(LegacyPackage::class);

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getPackage')->with('old_package')->willReturn($oldPackage);
        $packageManager->expects($this->once())->method('updatePackage')->with(
            $oldPackage,
            [
                'foo' => 'bar',
                'FileName' => 'file_name',
                'FileLocation' => 'temp_file',
            ],
            true,
            true,
            false,
            true,
            false,
            true,
        );

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('tempnam')
            ->with(ini_get('upload_tmp_dir'), 'braintacle_upload_')
            ->willReturn('temp_file');

        $builder = $this->createBuilder(
            packageManager: $packageManager,
            filesystem: $filesystem,
        );

        $package = $this->createStub(PackageUpdate::class);
        $package->method('toArray')->willReturn(['foo' => 'bar']);
        $package->action = Action::Execute;
        $package->deployPending = true;
        $package->deployRunning = false;
        $package->deploySuccess = true;
        $package->deployError = false;
        $package->deployGroups = true;

        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_OK);
        $uploadedFile->method('getClientFilename')->willReturn('file_name');
        $uploadedFile->expects($this->once())->method('moveTo')->with('temp_file');

        $builder->update($package, $uploadedFile, 'old_package');
    }
}
