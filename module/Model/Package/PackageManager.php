<?php

/**
 * Package manager
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

use Laminas\Db\Sql\Predicate;

/**
 * Package manager
 */
class PackageManager
{
    /**
     * Service manager
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $_serviceManager;

    /**
     * Constructor
     *
     * @param \Laminas\ServiceManager\ServiceManager $serviceManager
     */
    public function __construct(\Laminas\ServiceManager\ServiceManager $serviceManager)
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
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Return all packages including deployment statistics
     *
     * @param string $order Property to sort by
     * @param string $direction One of [asc|desc]
     * @return \Laminas\Db\ResultSet\AbstractResultSet Result set producing \Model\Package\Package
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
                     array('name' => 'DOWNLOAD', 'ivalue' => new \Laminas\Db\Sql\Literal('fileid'))
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
     */
    public function buildPackage(array $data, bool $deleteSource): void
    {
        $this->_serviceManager->get(PackageBuilder::class)->buildPackage($data, $deleteSource);
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
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
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
        $oldId = $package['Id'];
        $oldName = $package['Name'];

        $this->buildPackage($newPackageData, $deleteSource);
        $newPackage = $this->getPackage($newPackageData['Name']);
        $this->updateAssignments(
            $oldId,
            $newPackage['Id'],
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

        $where = new \Laminas\Db\Sql\Where();
        $where->equalTo('ivalue', $oldPackageId);
        $where->equalTo('name', 'DOWNLOAD');

        // Additional filters are only necessary if not all conditions are set
        if (!($deployPending and $deployRunning and $deploySuccess and $deployError and $deployGroups)) {
            $groups = $groupInfo->getSql()->select()->columns(array('hardware_id'));
            $filters = new \Laminas\Db\Sql\Where(null, \Laminas\Db\Sql\Where::COMBINED_BY_OR);
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
                $delete = new \Laminas\Db\Sql\Where();
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
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
