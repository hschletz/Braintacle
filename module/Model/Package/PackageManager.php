<?php
/**
 * Package manager
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

namespace Model\Package;

/**
 * Package manager
 */
class PackageManager
{
    /**
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Packages table
     * @var \Database\Table\Packages
     */
    protected $_packages;

    /**
     * PackageDownloadInfo table
     * @var \Database\Table\PackageDownloadInfo
     */
    protected $_packageDownloadInfo;

    /**
     * Constructor
     *
     * @param \Model\Config $config
     * @param \Database\Table\Packages $packages
     * @param \Database\Table\PackageDownloadInfo $packageDownloadInfo
     */
    public function __construct(
        \Model\Config $config,
        \Database\Table\Packages $packages,
        \Database\Table\packageDownloadInfo $packageDownloadInfo
    )
    {
        $this->_config = $config;
        $this->_packages = $packages;
        $this->_packageDownloadInfo = $packageDownloadInfo;
    }

    /**
     * Build a package
     *
     * @param array $data Package data
     * @throws \InvalidArgumentException if 'Platform' key is not a valid value
     */
    public function build($data)
    {
        $timestamp = $data['Timestamp']->get(\Zend_Date::TIMESTAMP);
        switch ($data['Platform']) {
            case 'windows':
                $platform = 'WINDOWS';
                break;
            case 'linux':
                $platform = 'LINUX';
                break;
            case 'mac':
                $platform = 'MacOSX';
                break;
            default:
                throw new \InvalidArgumentException('Invalid platform: ' . $data['Platform']);
        }
        $this->_packages->insert(
            array(
                'fileid' => $timestamp,
                'name' => $data['Name'],
                'priority' => $data['Priority'],
                'fragments' => $data['NumFragments'],
                'size' => $data['Size'],
                'osname' => $platform,
                'comment' => $data['Comment'],
            )
        );
        $this->_packageDownloadInfo->insert(
            array(
                'fileid' => $timestamp,
                'info_loc' => $this->_config->packageBaseUriHttps,
                'pack_loc' => $this->_config->packageBaseUriHttp,
                'cert_path' => dirname($this->_config->packageCertificate),
                'cert_file' => $this->_config->packageCertificate,
            )
        );
    }
}
