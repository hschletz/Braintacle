<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Package\Action;
use Braintacle\Package\Assignments;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use LogicException;
use Model\Package\Package as LegacyPackage;
use Model\Package\PackageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(Builder::class)]
final class BuilderTest extends TestCase
{
    private function createBuilder(
        ?PackageManager $packageManager = null,
        ?Assignments $assignments = null,
        ?Filesystem $filesystem = null,
    ): Builder {
        return new Builder(
            $packageManager ?? $this->createStub(PackageManager::class),
            $assignments ?? $this->createStub(Assignments::class),
            $filesystem ?? $this->createStub(Filesystem::class),
        );
    }

    #[TestWith([Action::Store])]
    #[TestWith([Action::Launch])]
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

    public function testUpdatePackage()
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

        $filesystem = $this->createStub(Filesystem::class);
        $uploadedFile = $this->createStub(UploadedFileInterface::class);

        $builder = $this
            ->getMockBuilder(Builder::class)
            ->setConstructorArgs([$packageManager, $assignments, $filesystem])
            ->onlyMethods(['build'])
            ->getMock();
        $builder->expects($this->once())->method('build')->with($packageUpdate, $uploadedFile);

        $builder->update($packageUpdate, $uploadedFile, 'old_name');
    }
}
