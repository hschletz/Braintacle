<?php
/**
 * Base class for computers and groups
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
abstract class Model_ComputerOrGroup extends \ArrayObject
{
    /**
     * @internal
     * Scan value in 'devices' table
     */
    const SCAN_DISABLED = 0;

    /**
     * @internal
     * Scan value in 'devices' table
     */
    const SCAN_EXPLICIT = 2;

    /**
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Global cache for getConfig() results
     *
     * This is a 2-dimensional array: $_configCache[computer/group ID][option name] = value
     */
    protected static $_configCache = array();

    /**
     * Timestamp when a lock held by this instance will expire
     * @var Zend_Date
     */
    private $_lockTimeout;

    /**
     * Constructor
     */
    public function __construct($input=array(), $flags=0, $iteratorClass='ArrayIterator')
    {
        parent::__construct($input, $flags, $iteratorClass);
        $this->_config = \Library\Application::getService('Model\Config');
    }

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
     * Be careful when using locks with transactions. The lock is implemented as
     * a row in the 'locks' table and therefore affected by the transaction and
     * all sorts of concurrency issues. For best practice, call lock() and
     * unlock() outside the transaction. Catch any exception that may occur
     * inside the transaction and roll back before re-throwing the exception.
     * Otherwise the destructor would call unlock() inside an uncommitted
     * transcation which would be rolled back automatically on disconnect,
     * undoing the unlock() effect and keeping a stale lock in the database.
     *
     * Example without transaction:
     *
     *     $obj->lock();
     *     ...
     *     $obj->unlock();
     *
     * Example with transaction:
     *
     *     $obj->lock();
     *     $db->beginTransaction();
     *     try {
     *         ...
     *     } catch(Exception $e) {
     *         $db->rollBack();
     *         $obj->unlock(); // Not strictly necessary, only for clarity
     *         throw $e;
     *     }
     *     $db->commit();
     *     $obj->unlock();
     *
     * @return bool Success. Always check the result. FALSE means that a lock is in use.
     */
    public function lock()
    {
        $db = Model_Database::getAdapter();
        $id = $this->getId();
        $expire = $this->_config->lockValidity;

        // Check if a lock already exists. CURRENT_TIMESTAMP is fetched from the
        // database to ensure that the same timezone is used for comparisions.
        $lock = $db->fetchRow(
            'SELECT since, CURRENT_TIMESTAMP AS current FROM locks WHERE hardware_id=?',
            $id,
            \Zend_Db::FETCH_ASSOC
        );
        if ($lock) {
            // A lock already exists. Check its timestamp.
            $since = new Zend_Date($lock['since']);
            $current = new Zend_Date($lock['current']);
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
            $db = Model_Database::getAdapter();
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
     *
     * @return string[]
     */
    public function getInstallablePackages()
    {
        $db = Model_Database::getAdapter();
        $select = $db->select()
            ->from('download_available', 'name')
            ->joinLeftUsing('download_enable', 'fileid', array())
            ->where(
                'id NOT IN(SELECT ivalue FROM devices WHERE hardware_id=? AND name=\'DOWNLOAD\')',
                $this->getId()
            )
            ->where(
                'download_available.fileid NOT IN(
                SELECT pkg_id FROM download_history WHERE hardware_id=?)',
                $this->getId()
            )
            ->order('name');

        return $select->query()->fetchAll(\Zend_Db::FETCH_COLUMN);
    }


    /**
     * Mark a package for installation on this object
     *
     * @param string $name Package name
     * @return bool Success
     */
    public function installPackage($name)
    {
        $package = \Library\Application::getService('Model\Package\PackageManager')->getPackage($name);
        $db = Model_Database::getAdapter();

        // Check if the package is already installed or in the history
        $selectInstalled = $db->select()
            ->from('devices', 'hardware_id')
            ->where('hardware_id=?', $this->getId())
            ->where('ivalue=?', $package['Id'])
            ->where('name=\'DOWNLOAD\'');
        $selectHistory = $db->select()
            ->from('download_history', 'hardware_id')
            ->where('hardware_id=?', $this->getId())
            ->where('pkg_id=?', $package['Timestamp']->get(Zend_Date::TIMESTAMP));
        $select = $db->select()->union(array($selectInstalled, $selectHistory));

        // Only proceed if the query does not deliver any results
        if (!($select->query()->fetch())) {
            $db->insert(
                'devices',
                array(
                    'hardware_id' => $this->getId(),
                    'name' => 'DOWNLOAD',
                    'ivalue' => $package['Id'],
                    'comments' => date(\Model\Package\Assignment::DATEFORMAT),
                )
            );
        }
        return true;
    }


    /**
     * Unaffect a package from this object
     * @param string $name
     */
    public function unaffectPackage($name)
    {
        $db = Model_Database::getAdapter();

        $package = \Library\Application::getService('Model\Package\PackageManager')->getPackage($name);
        $db->delete(
            'devices',
            array(
                'hardware_id=?' => $this->getId(),
                'ivalue=?' => $package['Id'],
                "name LIKE 'DOWNLOAD%'"
            )
        );
    }

    /**
     * Get stored item-specific configuration value
     *
     * Returns configuration values stored for this computer or group. If no
     * explicit configuration is stored, NULL is returned. Note that a returned
     * setting is not necessarily in effect - it may be overridden
     * somewhere else.
     *
     * Any valid option name can be passed for $option, though most options have
     * no item-specific setting and would always return NULL. In addition to the
     * options defined in \Model\Config, the following options are available:
     *
     * - **allowScan:** If 0, prevents this computer or group from scanning any
     *                  networks.
     * - **scanThisNetwork:** Causes a computer to always scan networks with the
     *                        given address (not taking a network mask into
     *                        account), overriding the server's automatic
     *                        choice.
     *
     * packageDeployment, allowScan and scanSnmp are never evaluated if disabled
     * globally or by groups of which a computer is a member. For this reason,
     * these options can only be 0 (explicitly disabled if enabled on a higher
     * level) or NULL (inherit behavior) but never 1 - that would be the same as
     * NULL. It is not possible to enable this if disabled on a higher level.
     *
     * @param string $option Option name
     * @return mixed Stored value or NULL
     */
    public function getConfig($option)
    {
        $id = $this->getId();
        if (isset(self::$_configCache[$id]) and array_key_exists($option, self::$_configCache[$id])) {
            return self::$_configCache[$id][$option];
        }

        $column = 'ivalue';
        switch ($option) {
            case 'packageDeployment':
                $name = 'DOWNLOAD_SWITCH'; // differs from global option
                break;
            case 'allowScan':
                $name = 'IPDISCOVER';
                $ivalue = self::SCAN_DISABLED;
                break;
            case 'scanThisNetwork':
                $name = 'IPDISCOVER';
                $ivalue = self::SCAN_EXPLICIT;
                $column = 'tvalue';
                break;
            case 'scanSnmp':
                $name = 'SNMP_SWITCH'; // differs from global option
                break;
            default:
                $name = $this->_config->getDbIdentifier($option);
        }
        $select = Model_Database::getAdapter()
                  ->select()
                  ->from('devices', $column)
                  ->where('hardware_id = ?', $this->getId())
                  ->where('name = ?', $name);
        if (isset($ivalue)) {
            $select->where('ivalue = ?', $ivalue);
        }
        $value = $select->query()->fetch();
        if ($value) {
            $value = $value->$column;
        } else {
            $value = null;
        }
        $value = $this->_normalizeConfig($option, $value);

        self::$_configCache[$id][$option] = $value;
        return $value;
    }

    /**
     * Store item-specific configuration value
     *
     * See getConfig() for available options. Note that a stored setting is not
     * necessarily in effect - it may be overridden somewhere else.
     *
     * @param string $option Option name
     * @param mixed $value Value to store, NULL to reset to default
     */
    public function setConfig($option, $value)
    {
        $db = Model_Database::getAdapter();

        // Determine 'name' column in the 'devices' table
        if ($option == 'allowScan' or $option == 'scanThisNetwork') {
            $name = 'IPDISCOVER';
        } else {
            $name = $this->_config->getDbIdentifier($option);
            if ($option == 'packageDeployment' or $option == 'scanSnmp') {
                $name .= '_SWITCH';
            }
        }

        $value = $this->_normalizeConfig($option, $value);

        // Set affected columns
        if ($option == 'scanThisNetwork') {
            $columns = array(
                'ivalue' => self::SCAN_EXPLICIT,
                'tvalue' => $value
            );
        } else {
            $columns = array('ivalue' => $value);
        }

        // Filter for delete()/update()
        $condition = array(
            'hardware_id = ?' => $this->getId(),
            'name = ?' => $name,
        );

        $db->beginTransaction();
        if ($value === null) {
            // Unset option
            if ($name == 'IPDISCOVER') {
                // Also check ivalue to prevent accidental deletion of unrelated setting
                $condition['ivalue = ?'] = ($option == 'allowScan') ? self::SCAN_DISABLED : self::SCAN_EXPLICIT;
            }
            $db->delete('devices', $condition);
        } else {
            $oldValue = $this->getConfig($option);
            if ($oldValue === null) {
                // Not set yet, insert new record
                if ($name == 'IPDISCOVER') {
                    // There may already be an IPDISCOVER record with a
                    // different ivalue. Since these are mutually exclusive
                    // (at most 1 IPDISCOVER record per object), this must be
                    // deleted first.
                    $db->delete('devices', $condition);
                }
                $columns['hardware_id'] = $this->getId();
                $columns['name'] = $name;
                $db->insert('devices', $columns);
            } elseif ($oldValue != $value) {
                // Already set to a different value, update recort
                $db->update('devices', $columns, $condition);
            }
        }
        $db->commit();
        self::$_configCache[$this->getId()][$option] = $value;
    }

    /**
     * Get default configuration value
     *
     * This method returns the default setting for an option that overrides or
     * gets overriden by this object's setting. For groups, this is the global
     * setting. For Computers, it is determined from the global setting and/or
     * all groups of which the computer is a member.
     *
     * @param string $option Option name
     * @return mixed Default value or NULL
     */
    abstract public function getDefaultConfig($option);

    /**
     * Process config value before or after daterbase interaction
     *
     * @param string $option Option name
     * @param mixed $value Raw value
     * @return mixed Normalized value
     */
    protected function _normalizeConfig($option, $value)
    {
        if ($option == 'packageDeployment' or
            $option == 'scanSnmp' or
            $option == 'allowScan'
        ) {
            // These options are only evaluated if their default setting is
            // enabled, i.e. they only have an effect if they get disabled.
            // To keep things clearer in the database, the option is unset if
            // enabled, with the same effect (i.e. none).
            if ($value) {
                $value = null;
            }
        }
        return $value;
    }

    /**
     * Get all stored item-specific configuration values
     *
     * The returned array has 3 elements: 'Agent', 'Download' and 'Scan'. Each
     * of these is an array with name/value pairs of configured values.
     *
     * @return array[]
     */
    public function getAllConfig()
    {
        return array(
            'Agent' => array(
                'contactInterval' => $this->getConfig('contactInterval'),
                'inventoryInterval' => $this->getConfig('inventoryInterval'),
            ),
            'Download' => array(
                'packageDeployment' => $this->getConfig('packageDeployment') === null,
                'downloadPeriodDelay' => $this->getConfig('downloadPeriodDelay'),
                'downloadCycleDelay' => $this->getConfig('downloadCycleDelay'),
                'downloadFragmentDelay' => $this->getConfig('downloadFragmentDelay'),
                'downloadMaxPriority' => $this->getConfig('downloadMaxPriority'),
                'downloadTimeout' => $this->getConfig('downloadTimeout'),
            ),
            'Scan' => array(
                'allowScan' => $this->getConfig('allowScan') === null,
                'scanSnmp' => $this->getConfig('scanSnmp') === null,
            ),
        );
    }
}
