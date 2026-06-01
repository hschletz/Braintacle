<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Package\Build\SourceFile;
use Braintacle\Package\Build\SourceFileFactory;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(SourceFileFactory::class)]
#[UsesClass(SourceFile::class)]
final class SourceFileFactoryTest extends TestCase
{
    private function createFactory(?Filesystem $filesystem = null): SourceFileFactory
    {
        return new SourceFileFactory($filesystem ?? $this->createStub(Filesystem::class));
    }

    public function testFromUploadedFileOk()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('tempnam')->with(ini_get('upload_tmp_dir'), 'braintacle_upload_')->willReturn('_path');

        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_OK);
        $uploadedFile->method('getClientFilename')->willReturn('_name');
        $uploadedFile->expects($this->once())->method('moveTo')->with('_path');

        $factory = $this->createFactory($filesystem);
        $sourceFile = $factory->fromUploadedFile($uploadedFile);

        $this->assertInstanceOf(SourceFile::class, $sourceFile);
        $this->assertEquals('_name', $sourceFile->name);
        $this->assertEquals('_path', $sourceFile->path);
    }

    public function testFromUploadedFileNoFile()
    {
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_NO_FILE);

        $factory = $this->createFactory();
        $this->assertNull($factory->fromUploadedFile($uploadedFile));
    }

    #[TestWith([UPLOAD_ERR_CANT_WRITE])]
    #[TestWith([UPLOAD_ERR_EXTENSION])]
    #[TestWith([UPLOAD_ERR_FORM_SIZE])]
    #[TestWith([UPLOAD_ERR_INI_SIZE])]
    #[TestWith([UPLOAD_ERR_NO_TMP_DIR])]
    #[TestWith([UPLOAD_ERR_PARTIAL])]
    public function testFromUploadedFileError(int $errorCode)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File upload resulted in error code ' . $errorCode);

        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getError')->willReturn($errorCode);

        $factory = $this->createFactory();
        $factory->fromUploadedFile($uploadedFile);
    }

    public function testFromPath()
    {
        $root = vfsStream::setup();
        // Decode URL to verify basename()'s locale awareness
        $path = urldecode(vfsStream::newFile('Ä.txt')->at($root)->url());

        $factory = $this->createFactory();
        $sourceFile = $factory->fromPath($path);
        $this->assertEquals('Ä.txt', $sourceFile->name);
        $this->assertEquals($path, $sourceFile->path);
    }

    public function testFromPathEmptyString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path: ');

        $factory = $this->createFactory();
        $factory->fromPath('');
    }

    public function testFromPathNonexistentFile()
    {
        $root = vfsStream::setup();
        $path = $root->url() . '/test';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path: ' . $path);

        $factory = $this->createFactory();
        $factory->fromPath($path);
    }

    public function testFromPathDirectory()
    {
        $root = vfsStream::setup();
        $path = vfsStream::newDirectory('test')->at($root)->url();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path: ' . $path);

        $factory = $this->createFactory();
        $factory->fromPath($path);
    }
}
