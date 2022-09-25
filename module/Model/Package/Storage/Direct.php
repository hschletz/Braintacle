<?php

/**
 * Storage class for direct webserver access
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

namespace Model\Package\Storage;

/**
 * Storage class for direct webserver access
 *
 * Metadata XML and fragment files are stored in a directory named by package
 * timestamp below the package base directory. The base directory must be made
 * accessible by the webserver to make content downloadable by agents.
 */
class Direct implements StorageInterface
{
    /**
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Metadata
     * @var \Model\Package\Metadata
     */
    protected $_metadata;

    /**
     * Constructor
     * @param \Model\Config $config
     * @param \Model\Package\Metadata $metadata
     */
    public function __construct(\Model\Config $config, \Model\Package\Metadata $metadata)
    {
        $this->_config = $config;
        $this->_metadata = $metadata;
    }

    /** {@inheritdoc} */
    public function prepare($data)
    {
        return $this->createDirectory($data['Id']);
    }

    /** {@inheritdoc} */
    public function write($data, $file, $deleteSource)
    {
        try {
            $data['NumFragments'] = $this->writeContent($data, $file, $deleteSource);
            $this->writeMetadata($data);
        } catch (\Exception $e) {
            $this->cleanup($data['Id']);
            throw $e;
        }
        return $data['NumFragments'];
    }

    /** {@inheritdoc} */
    public function cleanup($id)
    {
        $dir = $this->getPath($id);
        if (is_dir($dir)) {
            $fileSystem = new \Symfony\Component\Filesystem\Filesystem();
            $fileSystem->remove($dir);
        }
    }

    /**
     * Create package directory
     *
     * @param integer $id Package ID
     * @return string Path to created directory
     */
    public function createDirectory($id)
    {
        $dir = $this->getPath($id);
        if (!@mkdir($dir)) {
            if (is_dir($dir)) {
                throw new \Model\Package\RuntimeException('Package directory already exists: ' . $dir);
            } else {
                throw new \Model\Package\RuntimeException('Could not create package directory: ' . $dir);
            }
        }
        return $dir;
    }

    /**
     * Get base directory for package storage
     *
     * @param integer $id Package ID
     * @return string Directory composed from application config and package timestamp
     */
    public function getPath($id)
    {
        return $this->_config->packagePath . '/' . $id;
    }

    /**
     * Write metadata XML file
     *
     * @param array $data Package data
     */
    public function writeMetadata($data)
    {
        $this->_metadata->setPackageData($data);
        if ($this->_config->validateXml) {
            $this->_metadata->forceValid();
        }
        $this->_metadata->write($this->getPath($data['Id']) . '/info');
    }

    /**
     * Read metadata XML file
     *
     * @param integer $id Package ID
     * @return array Package data, see \Model\Package\Metadata::getPackageData()
     */
    public function readMetadata($id)
    {
        $this->_metadata->load($this->getPath($id) . '/info');
        return $this->_metadata->getPackageData();
    }

    /**
     * Write Package content
     *
     * @param array $data Package data
     * @param string $file Source file
     * @param bool $deleteSource Delete source file
     * @return integer Number of fragments
     */
    public function writeContent($data, $file, $deleteSource)
    {
        $id = $data['Id'];
        $baseName = $this->getPath($id) . "/$id-";
        $fileSize = @$data['Size'];
        $maxFragmentSize = @$data['MaxFragmentSize'] * 1024; // Kilobytes => Bytes
        $fileSystem = new \Symfony\Component\Filesystem\Filesystem();
        if (!$data['FileLocation']) {
            // No file
            $numFragments = 0;
        } elseif ($fileSize == 0 or $maxFragmentSize == 0 or $fileSize <= $maxFragmentSize) {
            // Don't split, just copy/move/rename the file
            if ($deleteSource) {
                $fileSystem->rename($file, $baseName . '1', true);
            } else {
                $fileSystem->copy($file, $baseName . '1');
            }
            $numFragments = 1;
        } else {
            // Split file into fragments of nearly identical size no bigger than $maxFragmentSize.
            $fragmentSize = ceil($fileSize / ceil($fileSize / $maxFragmentSize));
            // Determine number of fragments by files actually written
            $numFragments = 0;
            $input = new \Library\FileObject($file, 'rb');
            while ($fragment = $input->fread($fragmentSize)) {
                $numFragments++;
                $fileSystem->dumpFile($baseName . $numFragments, $fragment);
            }
            unset($input); // Close file before eventually trying to delete it
            if ($deleteSource) {
                $fileSystem->remove($file);
            }
        }
        return $numFragments;
    }
}
