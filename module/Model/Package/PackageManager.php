<?php
/**
 * Package manager
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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
     * Service manager
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $_serviceManager;

    /**
     * Constructor
     *
     * @param \Zend\ServiceManager\ServiceManager $serviceManager
     */
    public function __construct(\Zend\ServiceManager\ServiceManager $serviceManager)
    {
        $this->_serviceManager = $serviceManager;
    }

    /**
     * Check for existing package
     *
     * @param string $name Package name
     * @return bool
     */
    public function packageExists($name)
    {
        $packages = $this->_serviceManager->get('Database\Table\Packages');
        $sql = $packages->getSql()->select()->columns(array('name'))->where(array('name' => $name));
        return (bool) $packages->selectWith($sql)->count();
    }

    /**
     * Retrieve existing package
     *
     * @param string $name Package name
     * @return \Model\Package\Package Package object containing all data except content and deployment statistics
     * @throws RuntimeException if no package with given name exists or an error occurs
     */
    public function getPackage($name)
    {
        $packages = $this->_serviceManager->get('Database\Table\Packages');
        $storage = $this->_serviceManager->get('Model\Package\Storage\Direct');

        $select = $packages->getSql()->select();
        $select->columns(array('fileid', 'name', 'priority', 'fragments', 'size', 'osname', 'comment'))
               ->where(array('name' => $name));

        try {
            $packages = $packages->selectWith($select);
            if (!$packages->count()) {
                throw new \RuntimeException("There is no package with name '$name'");
            }

            $package = $packages->current();
            $package->exchangeArray($storage->readMetadata($package['Id']) + $package->getArrayCopy());
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
     * @return \Zend\Db\ResultSet\AbstractResultSet Result set producing \Model\Package\Package
     */
    public function getPackages($order = null, $direction = 'asc')
    {
        $clientConfig = $this->_serviceManager->get('Database\Table\ClientConfig');
        $groupInfo = $this->_serviceManager->get('Database\Table\GroupInfo');
        $packages = $this->_serviceManager->get('Database\Table\Packages');

        // Subquery prototype for deployment statistics
        $subquery = $clientConfig->getSql()->select();
        $subquery->columns(array(new Predicate\Literal('COUNT(hardware_id)')))
                 ->where(
                     array('name' => 'DOWNLOAD', 'ivalue' => new \Zend\Db\Sql\Literal('fileid'))
                 );

        $groups = $groupInfo->getSql()->select()->columns(array('hardware_id'));
        $pending = clone $subquery;
        $pending->where(new Predicate\IsNull('tvalue'))
                ->where(new Predicate\NotIn('hardware_id', $groups));

        $running = clone $subquery;
        $running->where(array('tvalue' => \Model\Package\Assignment::RUNNING));

        $success = clone $subquery;
        $success->where(array('tvalue' => \Model\Package\Assignment::SUCCESS));

        $error = clone $subquery;
        $error->where(new Predicate\Like('tvalue', \Model\Package\Assignment::ERROR_PREFIX . '%'));

        $select = $packages->getSql()->select();
        $select->columns(
            array(
                'fileid',
                'name',
                'priority',
                'fragments',
                'size',
                'osname',
                'comment',
                'num_pending' => new Predicate\Expression('?', array($pending)),
                'num_running' => new Predicate\Expression('?', array($running)),
                'num_success' => new Predicate\Expression('?', array($success)),
                'num_error' => new Predicate\Expression('?', array($error)),
            )
        );

        if ($order) {
            if ($order == 'Timestamp') {
                $order = 'fileid';
            } else {
                $order = $packages->getHydrator()->extractName($order);
            }
            $select->order(array($order => $direction));
        }

        return $packages->selectWith($select);
    }

    /**
     * Get all package names
     *
     * @return string[]
     */
    public function getAllNames()
    {
        return $this->_serviceManager->get('Database\Table\Packages')->fetchCol('name');
    }

    /**
     * Build a package
     *
     * @param array $data Package data
     * @param bool $deleteSource Delete source file as soon as possible
     * @throws RuntimeException if a package with the requested name already exists or an error occurs
     * @throws \InvalidArgumentException if 'Platform' key is not a valid value
     */
    public function buildPackage($data, $deleteSource)
    {
        if ($this->packageExists($data['Name'])) {
            throw new RuntimeException("Package '$data[Name]' already exists");
            return false;
        }

        // Set package ID/timestamp
        $data['Id'] = $this->_serviceManager->get('Library\Now')->getTimestamp();

        $packages = $this->_serviceManager->get('Database\Table\Packages');
        $storage = $this->_serviceManager->get('Model\Package\Storage\Direct');
        try {
            // Obtain archive file
            $path = $storage->prepare($data);
            $file = $this->autoArchive($data, $path, $deleteSource);
            $archiveCreated = ($file != $data['FileLocation']);

            // Determine file size and hash if available
            if ($data['Platform'] == 'windows') {
                $data['HashType'] = 'SHA256';
            } else {
                $data['HashType'] = 'SHA1'; // UNIX agent only supports SHA1 and MD5
            }
            if ($data['FileLocation']) {
                $fileSize = @filesize($file);
                if (!$fileSize) {
                    throw new \RuntimeException("Could not determine size of '$file'");
                }
                $hash = @hash_file($data['HashType'], $file);
                if (!$hash) {
                    throw new \RuntimeException("Could not compute $data[HashType] hash of '$file'");
                }
            } else {
                // No file
                $fileSize = 0;
                $hash = null;
            }
            $data['Hash'] = $hash;
            $data['Size'] = $fileSize;

            // Write storage specific data
            $data['NumFragments'] = $storage->write($data, $file, $deleteSource || $archiveCreated);

            // Create database entries
            $insert = $packages->getHydrator()->extract(new \ArrayObject($data));
            if (!$insert['osname']) {
                throw new \InvalidArgumentException('Invalid platform: ' . $data['Platform']);
            }
            $packages->insert($insert);
        } catch (\Exception $e) {
            try {
                $this->deletePackage($data['Name']);
            } catch (\Exception $e2) {
                // Ignore error (package does probably not exist at this point)
                // and return original exception instead
            }
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
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

        $archiveManager = $this->_serviceManager->get('Library\ArchiveManager');
        switch ($data['Platform']) {
            case 'windows':
                $type = \Library\ArchiveManager::ZIP;
                break;
            default:
                // other platforms not implemented yet
                return $source;
        }
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
                $fileSystem = new \Symfony\Component\Filesystem\Filesystem;
                $fileSystem->remove($source);
            }
        } catch (\Exception $e) {
            if (isset($archive)) {
                $archiveManager->closeArchive($archive, true);
                $fileSystem = new \Symfony\Component\Filesystem\Filesystem;
                $fileSystem->remove($filename);
            }
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
        return $filename;
    }

    /**
     * Delete a package
     *
     * @param string $name Package name
     * @throws RuntimeException if an error occurs
     */
    public function deletePackage($name)
    {
        $packages = $this->_serviceManager->get('Database\Table\Packages');
        $clientConfig = $this->_serviceManager->get('Database\Table\ClientConfig');
        $storage = $this->_serviceManager->get('Model\Package\Storage\Direct');
        try {
            $select = $packages->getSql()->select()->columns(array('fileid'))->where(array('name' => $name));
            $package = $packages->selectWith($select)->current();
            if (!$package) {
                throw new \RuntimeException("Package '$name' does not exist");
            }
            $id = $package['Id'];
            $clientConfig->delete(
                array(
                    'ivalue' => $id,
                    "name != 'DOWNLOAD_SWITCH'",
                    "name LIKE 'DOWNLOAD%'",
                )
            );
            $packages->delete(array('fileid' => $id));
            $storage->cleanup($id);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
    }

    /**
     * Update a package
     *
     * Builds a new package from $newPackageData, calls updateAssignments() with
     * given parameters and deletes the old package. The passed package object
     * is updated with new package data.
     *
     * @param \Model\Package\Package $package package to be updated
     * @param array $newPackageData new package data
     * @param bool $deleteSource Delete source file as soon as possible
     * @param bool $deployPending Update assignments with status 'pending'
     * @param bool $deployRunning Update assignments with status 'running'
     * @param bool $deploySuccess Update assignments with status 'success'
     * @param bool $deployError Update assignments with status 'error'
     * @param bool $deployGroups Update assignments for groups
     */
    public function updatePackage(
        \Model\Package\Package $package,
        array $newPackageData,
        $deleteSource,
        $deployPending,
        $deployRunning,
        $deploySuccess,
        $deployError,
        $deployGroups
    ) {
        // Preserve attributes because they get overwritten
        $oldId = $package['Id'];
        $oldName = $package['Name'];

        $this->buildPackage($newPackageData, $deleteSource);

        // Update package object
        $package->exchangeArray($this->getPackage($newPackageData['Name']));

        $this->updateAssignments(
            $oldId,
            $package['Id'],
            $deployPending,
            $deployRunning,
            $deploySuccess,
            $deployError,
            $deployGroups
        );
        $this->deletePackage($oldName);
    }

    /**
     * Update package assignments
     *
     * Sets a new package on existing assignments. Updated assignments have
     * their status reset to "pending" and their options (force, schedule,
     * post cmd) removed.
     *
     * @param integer $oldPackageId package to be replaced
     * @param integer $newPackageId new package
     * @param bool $deployPending Update assignments with status 'pending'
     * @param bool $deployRunning Update assignments with status 'running'
     * @param bool $deploySuccess Update assignments with status 'success'
     * @param bool $deployError Update assignments with status 'error'
     * @param bool $deployGroups Update assignments for groups
     * @throws RuntimeException if an error occurs
     */
    public function updateAssignments(
        $oldPackageId,
        $newPackageId,
        $deployPending,
        $deployRunning,
        $deploySuccess,
        $deployError,
        $deployGroups
    ) {
        if (!($deployPending or $deployRunning or $deploySuccess or $deployError or $deployGroups)) {
            return; // nothing to do
        }

        $clientConfig = $this->_serviceManager->get('Database\Table\ClientConfig');
        $groupInfo = $this->_serviceManager->get('Database\Table\GroupInfo');

        $where = new \Zend\Db\Sql\Where;
        $where->equalTo('ivalue', $oldPackageId);
        $where->equalTo('name', 'DOWNLOAD');

        // Additional filters are only necessary if not all conditions are set
        if (!($deployPending and $deployRunning and $deploySuccess and $deployError and $deployGroups)) {
            $groups = $groupInfo->getSql()->select()->columns(array('hardware_id'));
            $filters = new \Zend\Db\Sql\Where(null, \Zend\Db\Sql\Where::COMBINED_BY_OR);
            if ($deployPending) {
                $filters->isNull('tvalue')->and->notIn('hardware_id', $groups);
            }
            if ($deployRunning) {
                $filters->equalTo('tvalue', \Model\Package\Assignment::RUNNING);
            }
            if ($deploySuccess) {
                $filters->equalTo('tvalue', \Model\Package\Assignment::SUCCESS);
            }
            if ($deployError) {
                $filters->like('tvalue', \Model\Package\Assignment::ERROR_PREFIX . '%');
            }
            if ($deployGroups) {
                $filters->in('hardware_id', $groups);
            }
            $where->addPredicate($filters);
        }

        $now = $this->_serviceManager->get('Library\Now')->format(\Model\Package\Assignment::DATEFORMAT);
        try {
            // Remove DOWNLOAD_* options from updated assignments
            $subquery = $clientConfig->getSql()
                                     ->select()
                                     ->columns(array('hardware_id'))
                                     ->where($where);
            // @codeCoverageIgnoreStart
            if ($clientConfig->getAdapter()->getPlatform()->getName() == 'MySQL') {
                // MySQL does not allow subquery on the same table for DELETE
                // statements. Fetch result as a list instead.
                $subquery = array_column($clientConfig->selectWith($subquery)->toArray(), 'hardware_id');
            }
            // @codeCoverageIgnoreEnd
            if ($subquery) {
                // $subquery is either an SQL statement or a non-empty array.
                $delete = new \Zend\Db\Sql\Where;
                $delete->equalTo('ivalue', $oldPackageId)
                       ->notEqualTo('name', 'DOWNLOAD_SWITCH')
                       ->in('hardware_id', $subquery)
                       ->like('name', 'DOWNLOAD_%');
                $clientConfig->delete($delete);
            }

            // Update package ID and reset status
            $clientConfig->update(
                array(
                    'ivalue' => $newPackageId,
                    'tvalue' => \Model\Package\Assignment::PENDING,
                    'comments' => $now,
                ),
                $where
            );
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), (integer) $e->getCode(), $e);
        }
    }
}
