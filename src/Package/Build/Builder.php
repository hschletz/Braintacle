<?php

namespace Braintacle\Package\Build;

use Braintacle\Package\Action;
use Braintacle\Package\Assignments;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use LogicException;
use Model\Package\PackageBuilder;
use Model\Package\PackageManager;
use Model\Package\RuntimeException as PackageRuntimeException;
use Throwable;

/**
 * Package builder.
 */
final class Builder
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(
        private PackageManager $packageManager,
        private PackageBuilder $packageBuilder,
        private Assignments $assignments,
    ) {}

    public function build(Package $package, ?SourceFile $sourceFile, bool $deleteSource): void
    {
        $this->packageBuilder->checkName($package->name);

        try {
            $buildData = $package->toArray();
            $buildData['Id'] = $this->packageBuilder->generateId();
            $buildData['HashType'] = $this->packageBuilder->getHashType($package->platform->value);

            if ($sourceFile) {
                $buildData = $this->prepareFile($buildData, $sourceFile, $deleteSource);
            } else {
                if ($package->action != Action::Execute) {
                    throw new LogicException('Missing file');
                }
                // Missing file is OK for the Execute action. The legacy code
                // requires an empty string in that case.
                $buildData['FileLocation'] = '';
                $buildData['Size'] = 0;
                $buildData['Hash'] = null;
            }
            $this->write($buildData, $deleteSource);
        } catch (Throwable $throwable) {
            try {
                $this->packageManager->deletePackage($package->name);
            } catch (Throwable) {
                // Ignore error (package may not exist at this point or only
                // partially) and return original exception instead
            }
            throw new PackageRuntimeException($throwable->getMessage(), previous: $throwable);
        }
    }

    public function update(PackageUpdate $package, ?SourceFile $sourceFile, string $oldPackageName): void
    {
        $this->build($package, $sourceFile, true);

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

    public function prepareFile(array $buildData, SourceFile $sourceFile, bool $deleteSource): array
    {
        $buildData['FileName'] = $sourceFile->name;
        $buildData['FileLocation'] = $sourceFile->path;
        $archive = $this->packageBuilder->autoArchive(
            $buildData,
            $this->packageBuilder->prepareStorage($buildData),
            $deleteSource,
        );
        $buildData['Archive'] = $archive;
        $buildData['Size'] = filesize($archive);
        $buildData['Hash'] = $this->packageBuilder->getFileHash($archive, $buildData['HashType']);

        return $buildData;
    }

    public function write(array $buildData, bool $deleteSource): void
    {
        $buildData['NumFragments'] = $this->packageBuilder->writeToStorage(
            $buildData,
            $buildData['Archive'] ?? '',
            $deleteSource,
        );
        $this->packageBuilder->writeToDatabase($buildData);
    }
}
