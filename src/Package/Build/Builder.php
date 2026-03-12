<?php

namespace Braintacle\Package\Build;

use Braintacle\Package\Action;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use LogicException;
use Model\Package\PackageManager;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Package builder.
 */
final class Builder
{
    public function __construct(private PackageManager $packageManager, private Filesystem $filesystem) {}

    public function build(Package $package, UploadedFileInterface $uploadedFile): void
    {
        $buildData = $this->prepare($package, $uploadedFile);
        $this->packageManager->buildPackage($buildData, true);
    }

    public function update(PackageUpdate $package, UploadedFileInterface $uploadedFile, string $oldPackageName): void
    {
        $oldPackage = $this->packageManager->getPackage($oldPackageName);
        $buildData = $this->prepare($package, $uploadedFile);
        $this->packageManager->updatePackage(
            $oldPackage,
            $buildData,
            true,
            $package->deployPending,
            $package->deployRunning,
            $package->deploySuccess,
            $package->deployError,
            $package->deployGroups,
        );
    }

    private function prepare(Package $package, UploadedFileInterface $uploadedFile): array
    {
        $buildData = $package->toArray();

        if ($uploadedFile->getError() == UPLOAD_ERR_NO_FILE) {
            if ($package->action == Action::Execute) {
                // No file is OK for this action. The legacy builder requires an
                // empty string in that case.
                $buildData['FileLocation'] = '';
            } else {
                throw new LogicException('Missing file');
            }
        } else {
            $buildData['FileName'] = $uploadedFile->getClientFilename();

            // The legacy builder does not support streams and requires a
            // filename. To get the filename from an UploadedFileInterface
            // object, the file has to be moved to an explicitly provided path.
            // Use a temporary file within the upload directory (effectively
            // just a rename operation) to avoid unnecessary copies.
            $fileName = $this->filesystem->tempnam(ini_get('upload_tmp_dir'), 'braintacle_upload_');
            $uploadedFile->moveTo($fileName);
            $buildData['FileLocation'] = $fileName;
        }

        return $buildData;
    }
}
