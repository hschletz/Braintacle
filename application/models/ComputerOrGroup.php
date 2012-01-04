<?php
/**
 * Base class for computers and groups
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Models
 * @filesource
 */
/**
 * Base class for computers and groups
 *
 * Computers and groups share some common functionality. For example, they can
 * have packages assigned, and concurrent writes are controlled via a locking
 * mechanism.
 * Since the database schema does not distinct between computers and groups,
 * the implementation at database level is identical for these objects. This
 * class implements the common functionality for both.
 * @package Models
 */
abstract class Model_ComputerOrGroup extends Model_Abstract
{

    /**
     * Timestamp when a lock held by this instance will expire
     * @var Zend_Date
     */
   private $_lockTimeout;


    /**
     * Destructor
     *
     * Unlocks this object if a lock is held.
     */
    function __destruct()
    {
        $this->unlock();
    }


    /**
     * Lock this object (prevent altering by communication server or another console user)
     *
     * The lock will be released automatically in the destructor. It can also
     * be released manually via {@link unlock()}.
     *
     * @return bool Success. Always check the result. FALSE means that a lock is in use.
     */
    public function lock()
    {
        $db = Zend_Registry::get('db');
        $id = $this->getId();
        $expire = Model_Config::get('LockValidity');

        // Check if a lock already exists. CURRENT_TIMESTAMP is fetched from the
        // database to ensure that the same timezone is used for comparisions.
        $lock = $db->fetchRow(
            'SELECT since, CURRENT_TIMESTAMP AS current FROM locks WHERE hardware_id=?',
            $id
        );

        if ($lock) {
            // A lock already exists. Check its timestamp.
            $since = new Zend_Date($lock->since);
            $current = new Zend_Date($lock->current);
            if ($current->sub($since)->get() >= $expire) {
                // The existing lock is stale and can be reused.
                $db->update(
                    'locks',
                    array('since' => new Zend_Db_Expr('CURRENT_TIMESTAMP')),
                    array('hardware_id=?' => $id)
                );
                $success = true;
            } else {
                // The existing lock is still valid. The object can not be
                // locked at this time.
                $success = false;
            }
        } else {
            // No lock present yet. Create one. Another process might have
            // created one in the meantime, causing the insertion to fail.
            // In that case, the database exception is silently caught and
            // the lock does not get created by this instance.
            try {
                $db->insert(
                    'locks',
                    array(
                        'hardware_id' => $id,
                        'since' => new Zend_Db_Expr('CURRENT_TIMESTAMP')
                    )
                );
                $success = true;
            } catch (Exception $e) {
                $success = false;
            }
        }

        if ($success) {
            // Keep track of the lock inside this instance.
            $this->_lockTimeout = new Zend_Date;
            $this->_lockTimeout->add($expire);
        }

        return $success;
    }


    /**
     * Unlock this object
     *
     * Only locks created by the same instance of this class can be unlocked.
     */
    public function unlock()
    {
        if (!$this->isLocked()) {
            // No lock was created in this instance. Do nothing.
            return;
        }

        $current = new Zend_Date;
        if ($current->compare($this->_lockTimeout) == 1) {
            // This instance's lock has expired. Do not touch the database -
            // the lock there might no longer belong to this instance.
            // This should never happen unless the lock validity time is way too
            // short. This incident will be logged to inform the administrator
            // about the misconfiguration.
            error_log('Braintacle error: lock expired prematurely. Increase LOCK_REUSE_TIME.');
        } else {
            // Delete lock from database
            $db = Zend_Registry::get('db');
            $db->delete('locks', array('hardware_id=?' => $this->getId()));
        }
        // reset tracker
        $this->_lockTimeout = null;
    }


    /**
     * Check whether this object is locked
     *
     * Returns TRUE if {@link lock()} has been successfully called on this instance.
     * @return bool
     */
    public function isLocked()
    {
        return !is_null($this->_lockTimeout);
    }


    /**
     * Get a list of installable packages for this object
     *
     * A package is installable if it is not already assigned and not listed
     * in the history for a computer.
     * @return Zend_Db_Statement
     */
    function getInstallablePackages()
    {
        $db = Zend_Registry::get('db');

        /* The CAST(pkg_id AS CHAR(11)) expression is the attempt for a
         * statement compatible with all DBMS. An integer-to string-cast is
         * required by PostgreSQL. VARCHAR is not accepted by MySQL. CHAR would
         * be interpreted as CHAR(1) by PostgreSQL. So we end up with CHAR(11)
         * which appears to work with both. This should better be fixed in the
         * schema to avoid the cast alltogether.
         */
        $select = $db->select()
            ->from(
                'download_available', array(
                    'fileid',
                    'name',
                )
            )
            ->joinLeftUsing(
                'download_enable', 'fileid', array(
                    'id',
                )
            )
            ->where(
                'id NOT IN(SELECT ivalue FROM devices WHERE hardware_id=? AND name=\'DOWNLOAD\')',
                $this->getId()
            )
            ->where(
                'download_available.fileid NOT IN(
                SELECT CAST(pkg_id AS CHAR(11)) FROM download_history WHERE hardware_id=?)',
                $this->getId()
            )
            ->order('name');

        return $select->query();
    }


    /**
     * Mark a package for installation on this object
     *
     * @param string $name Package name
     * @return bool Success
     */
    public function installPackage($name)
    {
        $package = new Model_Package;
        if ($package->fromName($name)) {
            $db = Zend_Registry::get('db');

            // Check if the package is already installed or in the history
            $selectInstalled = $db->select()
                ->from('devices', 'hardware_id')
                ->where('hardware_id=?', $this->getId())
                ->where('ivalue=?', $package->getEnabledId())
                ->where('name=\'DOWNLOAD\'');
            $selectHistory = $db->select()
                ->from('download_history', 'hardware_id')
                ->where('hardware_id=?', $this->getId())
                ->where('pkg_id=?', $package->getTimestamp());
            $select = $db->select()->union(array($selectInstalled, $selectHistory));

            // Only proceed if the query does not deliver any results
            if (!($select->query()->fetch())) {
                $db->insert(
                    'devices',
                    array(
                        'hardware_id' => $this->getId(),
                        'name' => 'DOWNLOAD',
                        'ivalue' => $package->getEnabledId(),
                        'comments' => Model_Package::getLocaltimeCompat(),
                    )
                );
            }
            return true;
        } else {
            return false;
        }
    }


    /**
     * Unaffect a package from this object
     * @param string $name
     */
    public function unaffectPackage($name)
    {
        $db = Zend_Registry::get('db');

        $package = new Model_Package;
        if ($package->fromName($name)) {
            $db->delete(
                'devices',
                array(
                    'hardware_id=?' => $this->getId(),
                    'ivalue=?' => $package->getEnabledId(),
                    'name = \'DOWNLOAD\''
                )
            );
        }
    }

}
