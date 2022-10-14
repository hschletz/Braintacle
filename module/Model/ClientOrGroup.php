<?php

/**
 * Base class for clients and groups
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

namespace Model;

use Laminas\Db\Sql\Predicate\Operator;
use Nada\Column\AbstractColumn as Column;

/**
 * Base class for clients and groups
 *
 * Clients and groups share some common functionality. They can have packages
 * assigned, support individual configuration and concurrent writes are
 * controlled via a locking mechanism.
 * Since there is no database-level distinction between clients and groups for
 * the implementation of this functionality, this class implements the common
 * functionality for both objects.
 */
abstract class ClientOrGroup extends AbstractModel
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
     * Service Locator
     * @var \Laminas\ServiceManager\ServiceLocatorInterface
     */
    protected $_serviceLocator;

    /**
     * Cache for getConfig() results
     * @var array
     */
    protected $_configCache = array();

    /**
     * Timestamp when a lock held by this instance will expire
     * @var \DateTime
     */
    protected $_lockTimeout;

    /**
     * Lock nesting level counter
     * @var integer
     */
    protected $_lockNestCount = 0;

    /**
     * All config options
     * @var string[]
     */
    private $_options = [
        'contactInterval',
        'inventoryInterval',
        'packageDeployment',
        'downloadPeriodDelay',
        'downloadCycleDelay',
        'downloadFragmentDelay',
        'downloadMaxPriority',
        'downloadTimeout',
        'allowScan',
        'scanSnmp',
    ];

    /**
     * Options which can only be disabled
     * @var string[]
     */
    private $_optionsDisableOnly = [
        'packageDeployment',
        'allowScan',
        'scanSnmp',
    ];

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->_lockNestCount > 1) {
            $this->_lockNestCount = 1;
        }
        $this->unlock();
    }

    /**
     * Set service locator
     *
     * This should usually be called by a factory.
     *
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;
    }

    /**
     * Lock this object (prevent altering by server or another console user)
     *
     * The lock will be released automatically in the destructor. It can also
     * be released manually via unlock().
     *
     * Locks can be nested. If lock() is called more than once on the same
     * instance, the lock is only released after a matching number of unlock()
     * calls or in the destructor. This does not refresh the expiry timeout -
     * only the first lock() call sets the timeout.
     *
     * The lock is implemented as a row in the "locks" table. This must be
     * accounted for when using transactions.
     *
     * @return bool TRUE if the object could be locked (i.e. not already locked by another process)
     */
    public function lock()
    {
        if ($this->isLocked()) {
            $this->_lockNestCount++;
            return true;
        }

        $utc = new \DateTimeZone('UTC');
        // Current time is queried from the database which may run on another
        // server where the clock may differ. This guarantees consistent
        // reference across all operations (including Braintacle server).
        // The cast is necessary on some DBMS to get consistent timezone and
        // precision.
        $currentTimestamp = new \Laminas\Db\Sql\Literal(
            sprintf(
                'CAST(CURRENT_TIMESTAMP AS %s)',
                $this->_serviceLocator->get('Database\Nada')->getNativeDatatype(Column::TYPE_TIMESTAMP, null, true)
            )
        );
        $current = new \DateTime(
            $this->_serviceLocator->get('Db')->query(
                sprintf('SELECT %s AS current', $currentTimestamp->getLiteral()),
                \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
            )->current()['current'],
            $utc
        );
        $id = $this['Id'];
        $expireInterval = new \DateInterval(
            sprintf(
                'PT%dS',
                $this->_serviceLocator->get('Model\Config')->lockValidity
            )
        );
        $locks = $this->_serviceLocator->get('Database\Table\Locks');

        // Check if a lock already exists
        $select = $locks->getSql()->select();
        $select->columns(array('since'))
               ->where(array('hardware_id' => $id));
        $lock = $locks->selectWith($select)->current();
        if ($lock) {
            // A lock exists. Check its timestamp.
            $expire = new \DateTime($lock['since'], $utc);
            $expire->add($expireInterval);
            if ($current > $expire) {
                // The existing lock is stale and can be reused.
                $locks->update(
                    array('since' => $currentTimestamp),
                    array('hardware_id' => $id)
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
                $locks->insert(
                    array(
                        'hardware_id' => $id,
                        'since' => $currentTimestamp
                    )
                );
                $success = true;
            } catch (\Exception $e) {
                $success = false;
            }
        }

        if ($success) {
            // Keep track of the lock inside this instance.
            $this->_lockTimeout = $current;
            $this->_lockTimeout->add($expireInterval);
            $this->_lockNestCount++;
        }

        return $success;
    }


    /**
     * Unlock this object
     *
     * Only locks created by the current instance can be unlocked.
     */
    public function unlock()
    {
        if (!$this->isLocked()) {
            return;
        }
        if ($this->_lockNestCount > 1) {
            $this->_lockNestCount--;
            return;
        }

        // Query time from database for consistent reference across all operations
        $current = new \DateTime(
            $this->_serviceLocator->get('Db')->query(
                sprintf(
                    'SELECT CAST(CURRENT_TIMESTAMP AS %s) AS current',
                    $this->_serviceLocator->get('Database\Nada')->getNativeDatatype(
                        Column::TYPE_TIMESTAMP,
                        null,
                        true
                    )
                ),
                \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
            )->current()['current'],
            new \DateTimeZone('UTC')
        );
        $isExpired = ($current > $this->_lockTimeout);
        $this->_lockTimeout = null;
        $this->_lockNestCount = 0;
        if ($isExpired) {
            // This instance's lock has expired. The database entry may no
            // longer belong to this instance. It will be deleted or reused by
            // a different instance.
            // This should never happen unless the lock validity time is too
            // short. Generate a warning about the misconfiguration.
            trigger_error('Lock expired prematurely. Increase lock lifetime.', E_USER_WARNING);
            // @codeCoverageIgnoreStart
        } else {
            // @codeCoverageIgnoreEnd
            $this->_serviceLocator->get('Database\Table\Locks')->delete(array('hardware_id' => $this['Id']));
        }
    }

    /**
     * Check whether this object is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        return (bool) $this->_lockNestCount;
    }

    /**
     * Get a list of installable packages for this object
     *
     * A package is installable if it is not already assigned and not listed
     * in a client's history. The latter is always the case for groups.
     *
     * @return string[]
     */
    public function getAssignablePackages()
    {
        $packages = $this->_serviceLocator->get('Database\Table\Packages');
        $select = $packages->getSql()->select();
        $select->columns(array('name'))
               ->join(
                   // assigned packages
                   'devices',
                   new \Laminas\Db\Sql\Predicate\PredicateSet(
                       array(
                           new Operator('ivalue', '=', 'fileid', Operator::TYPE_IDENTIFIER, Operator::TYPE_IDENTIFIER),
                           new \Laminas\Db\Sql\Predicate\Operator('devices.hardware_id', '=', $this['Id']),
                           // "DOWNLOAD" is always present, eventual "DOWNLOAD_*" rows exist in addition to that.
                           // The equality check is suficient here.
                           new \Laminas\Db\Sql\Predicate\Operator('devices.name', '=', 'DOWNLOAD'),
                       )
                   ),
                   array(),
                   \Laminas\Db\Sql\Select::JOIN_LEFT
               )
               ->join(
                   // packages from history
                   'download_history',
                   new \Laminas\Db\Sql\Predicate\PredicateSet(
                       array(
                           new Operator('pkg_id', '=', 'fileid', Operator::TYPE_IDENTIFIER, Operator::TYPE_IDENTIFIER),
                           new \Laminas\Db\Sql\Predicate\Operator('download_history.hardware_id', '=', $this['Id']),
                       )
                   ),
                   array(),
                   \Laminas\Db\Sql\Select::JOIN_LEFT
               )
               ->where(
                   // exclude rows containing data from joined tables
                   array(
                        new \Laminas\Db\Sql\Predicate\IsNull('devices.ivalue'),
                        new \Laminas\Db\Sql\Predicate\IsNull('download_history.pkg_id'),
                   )
               )->order('download_available.name');

        $result = array();
        foreach ($packages->selectWith($select) as $package) {
            $result[] = $package['Name'];
        }
        return $result;
    }

    /**
     * Assign a package to this object
     *
     * Non-assignable packages are ignored.
     *
     * @param string $name Package name
     */
    public function assignPackage($name)
    {
        if (in_array($name, $this->getAssignablePackages())) {
            $package = $this->_serviceLocator->get('Model\Package\PackageManager')->getPackage($name);
            $this->_serviceLocator->get('Database\Table\ClientConfig')->insert(
                array(
                    'hardware_id' => $this['Id'],
                    'name' => 'DOWNLOAD',
                    'ivalue' => $package['Id'],
                    'tvalue' => \Model\Package\Assignment::PENDING,
                    'comments' => $this->_serviceLocator->get('Library\Now')->format(
                        \Model\Package\Assignment::DATEFORMAT
                    ),
                )
            );
        }
    }

    /**
     * Remove an assigned package from this object
     *
     * @param string $name
     */
    public function removePackage($name)
    {
        $package = $this->_serviceLocator->get('Model\Package\PackageManager')->getPackage($name);
        $this->_serviceLocator->get('Database\Table\ClientConfig')->delete(
            array(
                'hardware_id' => $this['Id'],
                'ivalue' => $package['Id'],
                new \Laminas\Db\Sql\Predicate\Like('name', 'DOWNLOAD%')
            )
        );
    }

    /**
     * Get configuration value
     *
     * Returns configuration values stored for this object. If no explicit
     * configuration is stored, NULL is returned. A returned setting is not
     * necessarily in effect - it may be overridden somewhere else.
     *
     * Any valid option name can be passed for $option, though most options are
     * not object-specific and would always yield NULL. In addition to the
     * options defined in \Model\Config, the following options are available:
     *
     * - **allowScan:** If 0, prevents client or group members from scanning
     *   networks.
     *
     * - **scanThisNetwork:** Causes a client to always scan networks with the
     *   given address (not taking a network mask into account), overriding the
     *   server's automatic choice.
     *
     * packageDeployment, allowScan and scanSnmp are never evaluated if disabled
     * globally or by groups of which a client is a member. For this reason,
     * these options can only be 0 (explicitly disabled if enabled on a higher
     * level) or NULL (inherit behavior).
     *
     * Results are cached per instance.
     *
     * @param string $option Option name
     * @return mixed Stored value or NULL
     */
    public function getConfig($option)
    {
        $id = $this['Id'];
        if (array_key_exists($option, $this->_configCache)) {
            return $this->_configCache[$option];
        }

        $column = 'ivalue';
        switch ($option) {
            case 'packageDeployment':
                $name = 'DOWNLOAD_SWITCH'; // differs from global database option name
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
                $name = 'SNMP_SWITCH'; // differs from global database option name
                break;
            default:
                $name = $this->_serviceLocator->get('Model\Config')->getDbIdentifier($option);
        }
        $clientConfig = $this->_serviceLocator->get('Database\Table\ClientConfig');
        $select = $clientConfig->getSql()->select();
        $select->columns(array($column))
               ->where(
                   array('hardware_id' => $id, 'name' => $name)
               );
        if (isset($ivalue)) {
            $select->where(array('ivalue' => $ivalue));
        }
        $row = $clientConfig->selectWith($select)->current();
        if ($row) {
            $value = $row[$column];
            if ($column == 'ivalue') {
                $value = (int) $value;
            }
            $value = $this->normalizeConfig($option, $value);
        } else {
            $value = null;
        }

        $this->_configCache[$option] = $value;
        return $value;
    }

    /**
     * Store configuration value
     *
     * See getConfig() for available options. A stored setting is not
     * necessarily in effect - it may be overridden somewhere else.
     *
     * @param string $option Option name
     * @param mixed $value Value to store, NULL to reset to default
     */
    public function setConfig($option, $value)
    {
        // Determine 'name' column in the 'devices' table
        if ($option == 'allowScan' or $option == 'scanThisNetwork') {
            $name = 'IPDISCOVER';
        } else {
            $name = $this->_serviceLocator->get('Model\Config')->getDbIdentifier($option);
            if ($option == 'packageDeployment' or $option == 'scanSnmp') {
                $name .= '_SWITCH';
            }
        }

        if ($value !== null and $option != 'scanThisNetwork') {
            $value = (int) $value; // Strict type required for cache
        }
        $value = $this->normalizeConfig($option, $value);

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
            'hardware_id' => $this['Id'],
            'name' => $name,
        );

        $clientConfig = $this->_serviceLocator->get('Database\Table\ClientConfig');
        $connection = $clientConfig->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            if ($value === null) {
                // Unset option. For scan options, also check ivalue to prevent
                // accidental deletion of unrelated setting.
                if ($option == 'allowScan') {
                    $condition['ivalue'] = self::SCAN_DISABLED;
                } elseif ($option == 'scanThisNetwork') {
                    $condition['ivalue'] = self::SCAN_EXPLICIT;
                }
                $clientConfig->delete($condition);
            } else {
                $oldValue = $this->getConfig($option);
                if ($oldValue === null) {
                    // Not set yet, insert new record
                    if ($name == 'IPDISCOVER' or $name == 'DOWNLOAD_SWITCH' or $name == 'SNMP_SWITCH') {
                        // There may already be a record with a different ivalue.
                        // For IPDISCOVER, this can happen because different $option
                        // values map to it. For *_SWITCH, this can happen if the
                        // database value is 1 (which is only possible if the record
                        // was not written by Braintacle), which getConfig() reports
                        // as NULL.
                        // Since there may only be 1 record per hardware_id/name,
                        // the old record must be deleted first.
                        $clientConfig->delete($condition);
                    }
                    $columns['hardware_id'] = $this['Id'];
                    $columns['name'] = $name;
                    $clientConfig->insert($columns);
                } elseif ($oldValue != $value) {
                    // Already set to a different value, update record
                    $clientConfig->update($columns, $condition);
                }
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
        $this->_configCache[$option] = $value;
    }

    /**
     * Get default configuration value
     *
     * This method returns the default setting for an option that overrides or
     * gets overriden by this object's setting. For groups, this is the global
     * setting. For clients, it is determined from the global setting and/or
     * all groups of which the client is a member.
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
    protected function normalizeConfig($option, $value)
    {
        if (in_array($option, $this->_optionsDisableOnly)) {
            // These options are only evaluated if their default setting is
            // enabled, i.e. they only have an effect if they get disabled.
            // To keep things clearer in the database, the option is unset if
            // enabled, with the same effect (i.e. none).
            if ($value != 0) {
                $value = null;
            }
        }
        return $value;
    }

    /**
     * Get all object-specific configuration values
     *
     * The returned array has 3 elements: 'Agent', 'Download' and 'Scan'. Each
     * of these is an array with name/value pairs of object-specific values. If
     * an option is not configured for this object, its value is NULL unless it
     * can only be disabled, not enabled. In that case, the returned value is 1
     * if it is not configured.
     *
     * Like with getConfig(), the returned options may not necessarily be
     * effective.
     *
     * @return array[]
     */
    public function getAllConfig()
    {
        $options = [];
        foreach ($this->_options as $option) {
            $value = $this->getConfig($option);
            if (in_array($option, $this->_optionsDisableOnly)) {
                $value = (int) ($value === null);
            }
            $options[$option] = $value;
        }
        return [
            'Agent' => [
                'contactInterval' => $options['contactInterval'],
                'inventoryInterval' => $options['inventoryInterval'],
            ],
            'Download' => [
                'packageDeployment' => $options['packageDeployment'],
                'downloadPeriodDelay' => $options['downloadPeriodDelay'],
                'downloadCycleDelay' => $options['downloadCycleDelay'],
                'downloadFragmentDelay' => $options['downloadFragmentDelay'],
                'downloadMaxPriority' => $options['downloadMaxPriority'],
                'downloadTimeout' => $options['downloadTimeout'],
            ],
            'Scan' => [
                'allowScan' => $options['allowScan'],
                'scanSnmp' => $options['scanSnmp'],
            ],
        ];
    }

    /**
     * Get all explicitly configured object-specific configuration values
     *
     * Returns a flat associative array with options which are explicitly
     * configured for this object. Unconfigured options are not returned.
     *
     * Like with getConfig(), the returned options may not necessarily be
     * effective.
     *
     * @return mixed[]
     */
    public function getExplicitConfig()
    {
        $options = [];
        foreach ($this->_options as $option) {
            $value = $this->getConfig($option);
            if ($value !== null) {
                $options[$option] = $value;
            }
        }
        return $options;
    }
}
