<?php

namespace Braintacle\Package\Build;

use Braintacle\Package\Action;
use Braintacle\Package\Assignments;
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
    public function __construct(
        private PackageManager $packageManager,
        private Assignments $assignments,
        private Filesystem $filesystem,
    ) {}

    public function build(Package $package, UploadedFileInterface $uploadedFile): void
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

        $this->packageManager->buildPackage($buildData, true);
    }

    public function update(PackageUpdate $package, UploadedFileInterface $uploadedFile, string $oldPackageName): void
    {
        $this->build($package, $uploadedFile);

        $newPackage = $this->packageManager->getPackage($package->name);
        $oldPackage = $this->packageManager->getPackage($oldPackageName);

        $this->assignments->updateAssignments(
            $oldPackage->id,
            $newPackage->id,
            $package->deployPending,
            $package->deployRunning,
            $package->deploySuccess,
            $package->deployError,
            $package->deployGroups,
        );
        $this->packageManager->deletePackage($oldPackageName);
    }
}
