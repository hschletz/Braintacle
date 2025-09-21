<?php

/**
 * Base class for clients and groups
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Database\Table\ClientConfig;
use Database\Table\Locks;
use Laminas\Db\Adapter\Adapter;
use Nada\Column\AbstractColumn as Column;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;

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

    protected ContainerInterface $container;

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
     * Options which can only be disabled
     * @var string[]
     */
    private $_optionsDisableOnly = [
        'packageDeployment',
        'allowScan',
        'scanSnmp',
    ];

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Lock this object (prevent altering by server or another console user)
     *
     * Locks should be released as soon as possible. A try/finally block can
     * prevent stale locks in case of an error. If a lock does not get released,
     * it will expire after the configured timeout.
     *
     * Locks can be nested. If lock() is called more than once on the same
     * instance, the lock is only released after a matching number of unlock()
     * calls. This does not refresh the expiry timeout - only the first lock()
     * call sets the timeout.
     *
     * The lock is implemented as a row in the "locks" table. This must be
     * accounted for when using transactions.
     *
     * @return bool TRUE if the object could be locked (i.e. not already locked
     * by another process)
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
                $this->container->get(AbstractDatabase::class)->getNativeDatatype(Column::TYPE_TIMESTAMP, null, true)
            )
        );
        $current = new \DateTime(
            $this->container->get(Adapter::class)->query(
                sprintf('SELECT %s AS current', $currentTimestamp->getLiteral()),
                \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
            )->current()['current'],
            $utc
        );
        $id = $this['Id'];
        $expireInterval = new \DateInterval(
            sprintf(
                'PT%dS',
                $this->container->get(Config::class)->lockValidity
            )
        );
        $locks = $this->container->get(Locks::class);

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
            $this->container->get(Adapter::class)->query(
                sprintf(
                    'SELECT CAST(CURRENT_TIMESTAMP AS %s) AS current',
                    $this->container->get(AbstractDatabase::class)->getNativeDatatype(
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
            $this->container->get(Locks::class)->delete(['hardware_id' => $this['Id']]);
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
                $name = $this->container->get(Config::class)->getDbIdentifier($option);
        }
        $clientConfig = $this->container->get(ClientConfig::class);
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
                if (in_array($option, $this->_optionsDisableOnly)) {
                    $value = (bool) $value;
                } else {
                    $value = (int) $value;
                }
            }
            $value = $this->normalizeConfig($option, $value);
        } else {
            $value = null;
        }

        $this->_configCache[$option] = $value;
        return $value;
    }

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
}
