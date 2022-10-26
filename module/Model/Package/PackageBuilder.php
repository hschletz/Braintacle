<?php

/**
 * Package builder
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

namespace Model\Package;

use Database\Table\Packages;
use InvalidArgumentException;
use Library\ArchiveManager;
use Model\Package\Storage\StorageInterface;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Package builder
 */
class PackageBuilder
{
    protected $packageManager;
    protected $archiveManager;
    protected $storage;
    protected $packagesTable;

    public function __construct(
        PackageManager $packageManager,
        ArchiveManager $archiveManager,
        StorageInterface $storage,
        Packages $packagesTable
    ) {
        $this->packageManager = $packageManager;
        $this->archiveManager = $archiveManager;
        $this->storage = $storage;
        $this->packagesTable = $packagesTable;
    }

    /**
     * Build a package
     *
     * @param array $data Package data
     * @param bool $deleteSource Delete source file as soon as possible
     * @throws RuntimeException if a package with the requested name already exists or an error occurs
     */
    public function buildPackage(array $data, bool $deleteSource): void
    {
        $this->checkName($data['Name']);
        try {
            $data['Id'] = $this->generateId();
            $file = $this->autoArchive($data, $this->prepareStorage($data), $deleteSource);

            $data['HashType'] = $this->getHashType($data['Platform']);
            if ($data['FileLocation']) {
                $fileinfo = new SplFileInfo($file);
                $data['Size'] = $fileinfo->getSize();
                $data['Hash'] = $this->getFileHash($file, $data['HashType']);
            } else {
                $data['Size'] = 0;
                $data['Hash'] = null;
            }

            $data['NumFragments'] = $this->writeToStorage($data, $file, $deleteSource);
            $this->writeToDatabase($data);
        } catch (Throwable $t) {
            try {
                $this->packageManager->deletePackage($data['Name']);
            } catch (Throwable $t2) {
                // Ignore error (package does probably not exist at this point)
                // and return original exception instead
            }
            throw new RuntimeException($t->getMessage(), (int) $t->getCode(), $t);
        }
    }

    /**
     * Check for existing package name.
     *
     * @throws RuntimeException if a package with the given name already exists
     */
    public function checkName(string $name): void
    {
        if ($this->packageManager->packageExists($name)) {
            throw new RuntimeException("Package '$name' already exists");
        }
    }

    /**
     * Generate ID for new package.
     */
    public function generateId(): int
    {
        return time();
    }

    /**
     * Get suitable hashing method for given platform.
     */
    public function getHashType(string $platform): string
    {
        if ($platform == 'windows') {
            $hashType = 'SHA256';
        } else {
            $hashType = 'SHA1'; // UNIX agent only supports SHA1 and MD5
        }

        return $hashType;
    }

    /**
     * Compute hash of given file using given method.
     */
    public function getFileHash(string $file, string $type): string
    {
        $hash = @hash_file($type, $file);
        if (!$hash) {
            throw new RuntimeException("Could not compute $type hash of '$file'");
        }

        return $hash;
    }

    /**
     * Prepare storage.
     *
     * @param array $data Package data
     * @return string Path for temporary file storage
     */
    public function prepareStorage(array $data): string
    {
        return $this->storage->prepare($data);
    }

    /**
     * Write package data to prepared storage backend.
     */
    public function writeToStorage(array $data, string $file, bool $deleteSource): int
    {
        $archiveCreated = ($file != $data['FileLocation']);

        return $this->storage->write($data, $file, $deleteSource || $archiveCreated);
    }

    /**
     * Create database entry.
     */
    public function writeToDatabase(array $data): void
    {
        $insert = $this->packagesTable->getHydrator()->extract(new Package($data));
        if (!$insert['osname']) {
            throw new InvalidArgumentException('Invalid platform: ' . $data['Platform']);
        }
        $this->packagesTable->insert($insert);
    }

    /**
     * Create a platform-specific archive if the source file is not already an
     * archive of the correct type
     *
     * This is currently only supported for the 'windows' platform which expects
     * a ZIP archive. If the Zip extension is not available, the source file is
     * assumed to be a ZIP archive and a warning is generated.
     *
     * The return value is the path to the archive file - either the source file
     * or a generated archive.
     *
     * @param array $data Package data
     * @param string $path Path where a new archive will be created
     * @param bool $deleteSource Delete source file after successfully creating an archive
     * @return string Path to archive file
     * @throws RuntimeException if an error occurs
     */
    public function autoArchive(array $data, string $path, bool $deleteSource): string
    {
        $source = $data['FileLocation'];
        if (!$source) {
            return $source;
        }

        switch ($data['Platform']) {
            case 'windows':
                $type = ArchiveManager::ZIP;
                break;
            default:
                // other platforms not implemented yet
                return $source;
        }

        $archiveManager = $this->archiveManager;
        if (!$archiveManager->isSupported($type)) {
            trigger_error("Support for archive type '$type' not available. Assuming archive.", E_USER_NOTICE);
            return $source;
        }
        if ($archiveManager->isArchive($type, $source)) {
            // Already an archive of reqired type. Do nothing.
            return $source;
        }

        try {
            $filename = "$path/archive";
            $archive = $archiveManager->createArchive($type, $filename);
            $archiveManager->addFile($archive, $source, $data['FileName']);
            $archiveManager->closeArchive($archive);
            if ($deleteSource) {
                $fileSystem = new Filesystem();
                $fileSystem->remove($source);
            }
        } catch (Throwable $t) {
            if (isset($archive)) {
                $archiveManager->closeArchive($archive, true);
                $fileSystem = new Filesystem();
                $fileSystem->remove($filename);
            }
            throw new RuntimeException($t->getMessage(), (int) $t->getCode(), $t);
        }

        return $filename;
    }
}
