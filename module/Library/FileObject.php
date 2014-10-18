<?php
/**
 * Extension to SplFileObject
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

/**
 * Extension to SplFileObject
 */
class FileObject extends \SplFileObject
{
    /**
     * Reads entire file into a string
     *
     * This is a wrapper for \file_get_contents() which throws an exception when
     * an error is encountered.
     *
     * @param string $filename Name of the file to read
     * @return string File content
     * @throws \RuntimeException if an error occurs during reading.
     */
    public static function fileGetContents($filename)
    {
        // Catch possible exceptions from stream wrappers
        $exception = null;
        try {
            $content = file_get_contents($filename);
        } catch (\Exception $exception) {
            $content = false;
        }
        if ($content === false) {
            throw new \RuntimeException("Error reading from file $filename", 0, $exception);
        } else {
            return $content;
        }
    }

    /**
     * Reads entire file into an array
     *
     * This is a wrapper for \file() which throws an exception when an error is
     * encountered.
     *
     * @param string $filename Name of the file to read
     * @param integer $flags Flags for \file()
     * @return string[] File content
     * @throws \RuntimeException if an error occurs during reading.
     */
    public static function fileGetContentsAsArray($filename, $flags=0)
    {
        // Catch possible exceptions from stream wrappers
        $exception = null;
        try {
            $content = file($filename, $flags);
        } catch (\Exception $exception) {
            $content = false;
        }
        if ($content === false) {
            throw new \RuntimeException("Error reading from file $filename", 0, $exception);
        } else {
            return $content;
        }
    }
}
