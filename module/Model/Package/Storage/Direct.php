<?php
/**
 * Storage class for direct webserver access
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
 * Storage class for direct webserver access
 *
 * Metadata XML and fragment files are stored in a directory named by package
 * timestamp below the package base directory. The base directory must be made
 * accessible by the webserver to make content downloadable by agents.
 */
class Direct
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

    /**
     * Get base directory for package storage
     *
     * @param array $data Package data
     * @return string Directory composed from application config and package timestamp
     */
    public function getPath($data)
    {
        return $this->_config->packagePath . '/' . $data['Timestamp']->get(\Zend_Date::TIMESTAMP);
    }

    /**
     * Write metadata XML file
     *
     * @param array $data Package data
     */
    public function writeMetadata($data)
    {
        $this->_metadata->setPackageData($data);
        $this->_metadata->save($this->getPath($data) . '/info');
    }
}
