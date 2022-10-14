<?php

/**
 * Frontend for different archive file types
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

namespace Library;

use Throwable;
use ZipArchive;

/**
 * Frontend for different archive file types
 *
 * This is a wrapper that provides a unified interface to different archive
 * types. It relies on PHP extensions or external libraries to support the
 * following archive types:
 *
 * - ZIP: requires Zip extension
 *
 * The methods assume that the requirements are met and will fail if not. Call
 * isSupported() to check for prerequisites.
 */
class ArchiveManager
{
    /**
     * ZIP archive type
     */
    const ZIP = 'zip';

    /**
     * Check for required extensions/libraries
     *
     * @param string $type Archive type to support, one of the class constants
     * @return bool
     * @throws \InvalidArgumentException if $type is unknown
     */
    public function isSupported($type)
    {
        switch ($type) {
            case self::ZIP:
                $isSupported = extension_loaded('zip');
                break;
            default:
                throw new \InvalidArgumentException('Unsupported archive type: ' . $type);
                break;
        }
        return $isSupported;
    }

    /**
     * Test file for given archive type
     *
     * This method merely tests if a file is technically an archive of the given
     * type, not that it's intended to be used as such. For example,
     * OpenDocument files are actually ZIP archives in disguise and will be
     * detected as such, although they would typically not be used like an
     * archive.
     *
     * @param string $type Archive type to test, one of the class constants
     * @param string $filename File to test, must exist
     * @return bool
     * @throws \InvalidArgumentException if $type is unknown
     */
    public function isArchive($type, $filename)
    {
        $isArchive = false;
        switch ($type) {
            case self::ZIP:
                $zip = new \ZipArchive();
                if ($zip->open($filename) === true) {
                    $zip->close();
                    $isArchive = true;
                }
                break;
            default:
                throw new \InvalidArgumentException('Unsupported archive type: ' . $type);
                break;
        }
        return $isArchive;
    }

    /**
     * Create an archive file
     *
     * The archive file must not exist - delete existing archives before calling
     * this method. Depending on the underlying archive implementation, the
     * archive may not be created on disk immediately, but only after
     * successfully adding files and/or closing the archive.
     *
     * @param string $type Archive type to create, one of the class constants
     * @param string $filename File to create
     * @return \ZipArchive Archive object
     * @throws \InvalidArgumentException if $type is unknown
     * @throws \RuntimeException if an error occurs
     */
    public function createArchive($type, $filename)
    {
        // ZipArchive::OVERWRITE gives strange errors on Windows if the target
        // file exists. The only known workaround is to forbid an existing file.
        if (file_exists($filename)) {
            throw new \RuntimeException('Archive already exists: ' . $filename);
        }
        switch ($type) {
            case self::ZIP:
                $archive = new \ZipArchive();
                // open() may throw an error on PHP 8 while earlier versions
                // return FALSE. Handle both cases uniformly.
                try {
                    $result = $archive->open($filename, \ZipArchive::CREATE | \ZipArchive::EXCL);
                    if ($result !== true) {
                        throw new \RuntimeException("Error creating ZIP archive '$filename', code $result");
                    }
                } catch (Throwable $t) {
                    throw new \RuntimeException("Error creating ZIP archive '$filename', code $result", 0, $t);
                }
                break;
            default:
                throw new \InvalidArgumentException('Unsupported archive type: ' . $type);
                break;
        }
        return $archive;
    }

    /**
     * Close an archive
     *
     * @param bool $ignoreErrors Don't throw an exception on error
     * @throws \InvalidArgumentException if $type is unknown
     * @throws \RuntimeException if an error occurs unless $ignoreErrors is TRUE
     */
    public function closeArchive(ZipArchive $archive, $ignoreErrors = false)
    {
        // close() may throw an error on PHP 8 while earlier versions return
        // FALSE. Handle both cases uniformly.
        try {
            if (!@$archive->close()) {
                throw new \RuntimeException('Error closing ZIP archive');
            }
        } catch (Throwable $t) {
            if (!$ignoreErrors) {
                throw new \RuntimeException('Error closing ZIP archive', 0, $t);
            }
        }
    }

    /**
     * Add a file to an archive
     *
     * Handling of file names with non-ASCII or potentially invalid characters
     * depends on the archive implementation, both for writing and reading. No
     * treatment of filenames is done. There is no guarantee that filenames with
     * problematic characters will work under all circumstances.
     *
     * @param string $file File to add
     * @param string $name Name/Path under which the file will be stored in the archive
     * @throws \InvalidArgumentException if $type is unknown
     * @throws \RuntimeException if an error occurs
     */
    public function addFile(ZipArchive $archive, $file, $name)
    {
        if (!@$archive->addFile($file, $name)) {
            throw new \RuntimeException("Error adding file '$file' to archive as '$name'");
        }
    }
}
