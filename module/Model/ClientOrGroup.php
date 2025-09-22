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
     * Timestamp when a lock held by this instance will expire
     * @var \DateTime
     */
    protected $_lockTimeout;

    /**
     * Lock nesting level counter
     * @var integer
     */
    protected $_lockNestCount = 0;

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
}
