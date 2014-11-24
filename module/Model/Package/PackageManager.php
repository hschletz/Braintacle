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
     * Archive manager
     * @var \Library\ArchiveManager
     */
    protected $_archiveManager;

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
     * @param \Library\ArchiveManager $archiveManager
     * @param \Database\Table\Packages $packages
     * @param \Database\Table\PackageDownloadInfo $packageDownloadInfo
     */
    public function __construct(
        \Model\Config $config,
        \Library\ArchiveManager $archiveManager,
        \Database\Table\Packages $packages,
        \Database\Table\packageDownloadInfo $packageDownloadInfo
    )
    {
        $this->_config = $config;
        $this->_archiveManager = $archiveManager;
        $this->_packages = $packages;
        $this->_packageDownloadInfo = $packageDownloadInfo;
    }

    /**
     * Check for existing package
     *
     * @param string $name Package name
     * @return bool
     */
    public function packageExists($name)
    {
        $sql = $this->_packages->getSql()->select()->columns(array('name'))->where(array('name' => $name));
        return (bool) $this->_packages->selectWith($sql)->count();
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
     */
    public function autoArchive($data, $path, $deleteSource)
    {
        $source = $data['FileLocation'];
        if (!$source) {
            return $source;
        }

        switch ($data['Platform']) {
            case 'windows':
                $type = \Library\ArchiveManager::ZIP;
                break;
            default:
                // other platforms not implemented yet
                return $source;
        }
        if (!$this->_archiveManager->isSupported($type)) {
            trigger_error("Support for archive type '$type' not available. Assuming archive.", E_USER_NOTICE);
            return $source;
        }
        if ($this->_archiveManager->isArchive($type, $source)) {
            // Already an archive of reqired type. Do nothing.
            return $source;
        }

        try {
            $filename = "$path/archive";
            $archive = $this->_archiveManager->createArchive($type, $filename);
            $this->_archiveManager->addFile($archive, $source, $data['FileName']);
            $this->_archiveManager->closeArchive($archive);
            if ($deleteSource) {
                \Library\FileObject::unlink($source);
            }
        } catch (\Exception $e) {
            if (isset($archive)) {
                $this->_archiveManager->closeArchive($archive, true);
                \Library\FileObject::unlink($filename);
            }
            throw $e;
        }
        return $filename;
    }
}
