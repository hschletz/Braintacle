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

use Zend\Db\Sql\Predicate;

/**
 * Package manager
 */
class PackageManager
{
    /**
     * Package storage
     * @var \Model\Package\Storage\StorageInterface
     */
    protected $_storage;

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
     * ClientConfig table
     * @var \Database\Table\ClientConfig
     */
    protected $_clientConfig;

    /**
     * GroupInfo table
     * @var \Database\Table\ClientConfig
     */
    protected $_groupInfo;

    /**
     * Constructor
     *
     * @param \Model\Package\Storage\StorageInterface $storage
     * @param \Model\Config $config
     * @param \Library\ArchiveManager $archiveManager
     * @param \Database\Table\Packages $packages
     * @param \Database\Table\PackageDownloadInfo $packageDownloadInfo
     * @param \Database\Table\ClientConfig $clientConfig
     * @param \Database\Table\GroupInfo $groupInfo
     */
    public function __construct(
        \Model\Package\Storage\StorageInterface $storage,
        \Model\Config $config,
        \Library\ArchiveManager $archiveManager,
        \Database\Table\Packages $packages,
        \Database\Table\packageDownloadInfo $packageDownloadInfo,
        \Database\Table\ClientConfig $clientConfig,
        \Database\Table\GroupInfo $groupInfo
    )
    {
        $this->_storage = $storage;
        $this->_config = $config;
        $this->_archiveManager = $archiveManager;
        $this->_packages = $packages;
        $this->_packageDownloadInfo = $packageDownloadInfo;
        $this->_clientConfig = $clientConfig;
        $this->_groupInfo = $groupInfo;
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
     * Retrieve existing package
     *
     * @param string $name Package name
     * @return \Model_Package Package object containing all data except content and deployment statistics
     * @throws RuntimeException if no package with given name exists or an error occurs
     */
    public function getPackage($name)
    {
        $select = $this->_packages->getSql()->select();
        $select->join('download_enable', 'download_available.fileid = download_enable.fileid', 'id')
               ->where(array('name' => $name));

        try {
            $packages = $this->_packages->selectWith($select);
            if (!$packages->count()) {
                throw new \RuntimeException("There is no package with name '$name'");
            }

            $package = $packages->current();
            $package->exchangeArray($this->_storage->readMetadata($package['Timestamp']));
            return $package;
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
    }

    /**
     * Return all packages including deployment statistics
     *
     * @param string $order Property to sort by
     * @param string $direction One of [asc|desc]
     * @return \Zend\Db\ResultSet\AbstractResultSet Result set producing \Model_Package
     */
    public function getPackages($order=null, $direction='asc')
    {
        // Subquery prototype for deployment statistics
        $subquery = $this->_clientConfig->getSql()->select();
        $subquery->columns(array(new Predicate\Literal('COUNT(hardware_id)')))
                 ->where(
                     array('name' => 'DOWNLOAD', 'ivalue' => new \Zend\Db\Sql\Literal('id'))
                 );

        $groups = $this->_groupInfo->getSql()->select()->columns(array('hardware_id'));
        $nonNotified = clone $subquery;
        $nonNotified->where(new Predicate\IsNull('tvalue'))
                    ->where(new Predicate\NotIn('hardware_id', $groups));

        $success = clone $subquery;
        $success->where(array('tvalue' => \Model_PackageAssignment::SUCCESS));

        $notified = clone $subquery;
        $notified->where(array('tvalue' => \Model_PackageAssignment::NOTIFIED));

        $error = clone $subquery;
        $error->where(new Predicate\Like('tvalue', \Model_PackageAssignment::ERROR_PREFIX . '%'));

        $select = $this->_packages->getSql()->select();
        $select->columns(
            array(
                '*',
                'num_nonnotified' => new Predicate\Expression('?', array($nonNotified)),
                'num_success' => new Predicate\Expression('?', array($success)),
                'num_notified' => new Predicate\Expression('?', array($notified)),
                'num_error' => new Predicate\Expression('?', array($error)),
            )
        );
        $select->join('download_enable', 'download_available.fileid = download_enable.fileid', 'id');

        $package = new \Model_Package;
        $select->order(\Model_Package::getOrder($order, $direction, $package->getPropertyMap()));

        return $this->_packages->selectWith($select);
    }

    /**
     * Build a package
     *
     * @param array $data Package data
     * @param bool $deleteSource Delete source file as soon as possible
     * @throws RuntimeException if a package with the requested name already exists or an error occurs
     * @throws \InvalidArgumentException if 'Platform' key is not a valid value
     * @return integer ID of created database entry
     */
    public function build($data, $deleteSource)
    {
        // Validate input data
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
        if ($this->packageExists($data['Name'])) {
            throw new RuntimeException("Package '$data[Name]' already exists");
            return false;
        }

        // Set package timestamp
        $data['Timestamp'] = new \Zend_Date;
        $timestamp = $data['Timestamp']->get(\Zend_Date::TIMESTAMP);

        try {
            // Obtain archive file
            $path = $this->_storage->prepare($data);
            $file = $this->autoArchive($data, $path, $deleteSource);
            $archiveCreated = ($file != $data['FileLocation']);

            // Determine file size and hash if available
            if ($data['FileLocation']) {
                $fileSize = @filesize($file);
                if (!$fileSize) {
                    throw new \RuntimeException("Could not determine size of '$file'");
                }
                $hash = @sha1_file($file);
                if (!$hash) {
                    throw new \RuntimeException("Could not compute SHA1 hash of '$file'");
                }
            } else {
                // No file
                $fileSize = 0;
                $hash = null;
            }
            $data['Hash'] = $hash;
            $data['Size'] = $fileSize;

            // Write storage specific data
            $data['NumFragments'] = $this->_storage->write($data, $file, $deleteSource || $archiveCreated);

            // Create database entries
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
                )
            );

            // Get ID of created record
            $select = $this->_packageDownloadInfo->getSql()->select();
            $select->columns(array('id'))->where(array('fileid' => $timestamp));
            $id = $this->_packageDownloadInfo->selectWith($select)->current()['id'];
        } catch (\Exception $e) {
            $this->delete($data);
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
        return (integer) $id;
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
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
        return $filename;
    }

    /**
     * Delete a package
     *
     * @param array $data Package data
     * @throws RuntimeException if an error occurs
     */
    public function delete($data)
    {
        $timestamp = $data['Timestamp']->get(\Zend_Date::TIMESTAMP);
        try {
            $this->_clientConfig->delete(
                array(
                    "name != 'DOWNLOAD_SWITCH'",
                    "name LIKE 'DOWNLOAD%'",
                    'ivalue IN (SELECT id FROM download_enable WHERE fileid = ?)' => $timestamp,
                )
            );
            $this->_packageDownloadInfo->delete(array('fileid' => $timestamp));
            $this->_packages->delete(array('fileid' => $timestamp));
            $this->_storage->cleanup($data);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
    }

    /**
     * Update package assignments
     *
     * Sets a new package on existing assignments. Updated assignments have
     * their status reset to "not notified" and their options (force, schedule,
     * post cmd) removed.
     *
     * @param integer $oldPackageId package to be replaced
     * @param integer $newPackageId new package
     * @param bool $deployNonnotified Update assignments with status 'not notified'
     * @param bool $deploySuccess Update assignments with status 'success'
     * @param bool $deployNotified Update assignments with status 'notified'
     * @param bool $deployError Update assignments with status 'error'
     * @param bool $deployGroups Update assignments for groups
     * @throws RuntimeException if an error occurs
     */
    public function updateAssignments(
        $oldPackageId,
        $newPackageId,
        $deployNonnotified,
        $deploySuccess,
        $deployNotified,
        $deployError,
        $deployGroups
    )
    {
        if (!($deployNonnotified or $deploySuccess or $deployNotified or $deployError or $deployGroups)) {
            return; // nothing to do
        }

        $where = new \Zend\Db\Sql\Where;
        $where->equalTo('ivalue', $oldPackageId);

        // Additional filters are only necessary if not all conditions are set
        if (!($deployNonnotified and $deploySuccess and $deployNotified and $deployError and $deployGroups)) {
            $groups = $this->_groupInfo->getSql()->select()->columns(array('hardware_id'));
            $filters = new \Zend\Db\Sql\Where(null, \Zend\Db\Sql\Where::COMBINED_BY_OR);
            if ($deployNonnotified) {
                $filters->isNull('tvalue')->and->notIn('hardware_id', $groups);
            }
            if ($deploySuccess) {
                $filters->equalTo('tvalue', \Model_PackageAssignment::SUCCESS);
            }
            if ($deployNotified) {
                $filters->equalTo('tvalue', \Model_PackageAssignment::NOTIFIED);
            }
            if ($deployError) {
                $filters->like('tvalue', \Model_PackageAssignment::ERROR_PREFIX . '%');
            }
            if ($deployGroups) {
                $filters->in('hardware_id', $groups);
            }
            $where->addPredicate($filters);
        }

        try{
            // Remove DOWNLOAD_* options from updated assignments
            $subquery = $this->_clientConfig->getSql()
                                            ->select()
                                            ->columns(array('hardware_id'))
                                            ->where(array('name' => 'DOWNLOAD', $where));
            $delete = new \Zend\Db\Sql\Where;
            $delete->equalTo('ivalue', $oldPackageId)
                   ->in('hardware_id', $subquery)
                   ->notEqualTo('name', 'DOWNLOAD_SWITCH')
                   ->like('name', 'DOWNLOAD_%');

            $this->_clientConfig->delete($delete);

            // Update package ID and reset status
            $this->_clientConfig->update(
                array(
                    'ivalue' => $newPackageId,
                    'tvalue' => \Model_PackageAssignment::NOT_NOTIFIED,
                    'comments' => date(\Model_PackageAssignment::DATEFORMAT),
                ),
                array('name' => 'DOWNLOAD', $where)
            );
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
    }
}
