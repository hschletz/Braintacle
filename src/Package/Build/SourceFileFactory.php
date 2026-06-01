<?php

namespace Braintacle\Package\Build;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Generate a SourceFile instance from various sources.
 */
final class SourceFileFactory
{
    public function __construct(private Filesystem $filesystem) {}

    /**
     * @return null|SourceFile Instance, or NULL if no file was uploaded
     * @throws RuntimeException if an upload error occurred
     */
    public function fromUploadedFile(UploadedFileInterface $uploadedFile): ?SourceFile
    {
        $error = $uploadedFile->getError();
        switch ($error) {
            case UPLOAD_ERR_OK:
                // The legacy package builder does not support streams and
                // requires a filename. To get the filename from an
                // UploadedFileInterface object, the file has to be moved to an
                // explicitly provided path. Use a temporary file within the
                // upload directory (effectively just a rename operation) to
                // avoid unnecessary copies.
                $path = $this->filesystem->tempnam(ini_get('upload_tmp_dir'), 'braintacle_upload_');
                $uploadedFile->moveTo($path);

                return new SourceFile($uploadedFile->getClientFilename(), $path);
            case UPLOAD_ERR_NO_FILE:
                return null;
            default:
                // TODO better error messages
                throw new RuntimeException('File upload resulted in error code ' . $error);
        }
    }

    public function fromPath(string $path): SourceFile
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException('Invalid path: ' . $path);
        }

        return new SourceFile(basename($path), $path);
    }
}
