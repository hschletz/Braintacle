<?php
/**
 * Interface for package storage
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

namespace Model\Package\Storage;

/**
 * Interface for package storage
 */
interface StorageInterface
{
    /**
     * Prepare environment for building a package
     *
     * This is invoked by the package manager as the first step of building a
     * package. Package data is set up except for any content-related fields.
     *
     * Implementations are responsible for cleanup if an error occurs.
     *
     * @param array $data Package data
     * @throws \Exception if an error occurs.
     */
    public function prepare($data);

    /**
     * Write all package data
     *
     * This is invoked by the package manager when the content is ready. Package
     * data is complete.
     *
     * Implementations must write Metadata and content and are responsible for
     * cleanup if an error occurs.
     *
     * @param array $data Package data
     * @param string $file Source file
     * @param bool $deleteSource Delete source file
     * @return integer Number of fragments
     * @throws \Exception if an error occurs.
     */
    public function write($data, $file, $deleteSource);

    /**
     * Clean up any resources created by prepare() and write()
     *
     * This is invoked by the package manager when a package is deleted or an
     * error occurs in the build process.
     *
     * @param array $data Package data
     * @throws \Exception if an error occurs.
     */
    public function cleanup($data);
}
