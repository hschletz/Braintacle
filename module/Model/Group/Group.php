<?php

/**
 * A group of clients
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

namespace Model\Group;

use DateTimeInterface;

/**
 * A group of clients
 *
 * Packages and settings assigned to a group apply to all members. Clients can
 * become a member by manual assignment or automatically based on the result of
 * a query. It is also possible to unconditionally exclude a client from a group
 * regardless of query result.
 */
class Group extends \Model\ClientOrGroup
{
    /**
     * Primary key
     */
    public int $Id;

    /**
     * Name
     */
    public string $Name;

    /**
     * Description
     */
    public string $Description;

    /**
     * Timestamp of group creation
     */
    public DateTimeInterface $CreationDate;

    /**
     * SQL query for dynamic members, may be empty
     */
    public string $DynamicMembersSql;

    /**
     * Timestamp of last cache update
     */
    public DateTimeInterface $CacheCreationDate;

    /**
     * Timestamp when cache will expire and get rebuilt
     */
    public DateTimeInterface $CacheExpirationDate;

    /** {@inheritdoc} */
    public function getDefaultConfig($option)
    {
        $config = $this->_serviceLocator->get('Model\Config');
        if ($option == 'allowScan') {
            if ($config->scannersPerSubnet == 0) {
                $value = 0;
            } else {
                $value = 1;
            }
        } else {
            $value = $config->$option;
        }
        return $value;
    }

    /**
     * Set group members based on query
     *
     * If $type is \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
     * the DynamicMembersSql property will be set to the resulting query. For
     * other values, the query will be executed and $type is stored as manual
     * membership/exclusion on the results.
     *
     * The query arguments are passed to \Model\Client\ClientManager::getClients().
     *
     * @param integer $type Membership type
     * @param string|array $filter Name or array of names of a pre-defined filter routine
     * @param string|array $search Search parameter(s) passed to the filter
     * @param string|array $operator Comparision operator
     * @param bool|array $invert Invert query results (return all clients NOT matching criteria)
     * @throws \LogicException if the query does not yield exactly 1 column (internal validation, should never fail)
     */
    public function setMembersFromQuery($type, $filter, $search, $operator, $invert)
    {
        $id = $this['Id'];
        $members = $this->_serviceLocator->get('Model\Client\ClientManager')->getClients(
            array('Id'),
            null,
            null,
            $filter,
            $search,
            $operator,
            $invert,
            false,
            true,
            ($type != \Model\Client\Client::MEMBERSHIP_AUTOMATIC)
        );

        if ($type == \Model\Client\Client::MEMBERSHIP_AUTOMATIC) {
            $numCols = count($members->getRawState(\Laminas\Db\Sql\Select::COLUMNS));
            foreach ($members->getRawState(\Laminas\Db\Sql\Select::JOINS) as $join) {
                $numCols += count($join['columns']);
            }
            if ($numCols != 1) {
                throw new \LogicException('Expected 1 column, got ' . $numCols);
            }
            $sql = new \Laminas\Db\Sql\Sql($this->_serviceLocator->get('Db'));
            $query = $sql->buildSqlString($members);
            $this->_serviceLocator->get('Database\Table\GroupInfo')->update(
                array('request' => $query),
                array('hardware_id' => $id)
            );
            $this->offsetSet('DynamicMembersSql', $query);
            $this->update(true); // Force cache update, effectively validating query
        } else {
            // Wait until lock can be obtained
            while (!$this->lock()) {
                sleep(1);
            }
            // Get list of existing memberships
            $existingMemberships = array();
            $groupMemberships = $this->_serviceLocator->get('Database\Table\GroupMemberships');
            $select = $groupMemberships->getSql()->select();
            $select->columns(array('hardware_id', 'static'))->where(array('group_id' => $id));
            foreach ($groupMemberships->selectWith($select) as $membership) {
                $existingMemberships[$membership['hardware_id']] = $membership['static'];
            }
            // Insert/update membership entries
            $connection = $groupMemberships->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();
            try {
                foreach ($members as $member) {
                    $member = $member['Id'];
                    if (isset($existingMemberships[$member])) {
                        // Update only memberships of a different type
                        if ($existingMemberships[$member] != $type) {
                            $groupMemberships->update(
                                array('static' => $type),
                                array('group_id' => $id, 'hardware_id' => $member)
                            );
                        }
                    } else {
                        $groupMemberships->insert(
                            array('group_id' => $id, 'hardware_id' => $member, 'static' => $type)
                        );
                    }
                }
                $connection->commit();
            } catch (\Exception $exception) {
                $connection->rollBack();
                $this->unlock();
                throw $exception;
            }
            $this->unlock();
        }
    }

    /**
     * Update the cache for dynamic memberships
     *
     * Dynamic memberships are always determined from the cache. This method
     * updates the cache for a group. By default, the cache is not updated
     * before its expiration time has been reached.
     *
     * @param bool $force always rebuild cache, ignoring expiration time
     */
    public function update($force = false)
    {
        if (!$this['DynamicMembersSql']) {
            return; // Nothing to do if no SQL query is defined for this group
        }

        $now = $this->_serviceLocator->get('Library\Now');
        // Do nothing if cache has not expired yet and update is not forced.
        if (!$force and $this['CacheExpirationDate'] > $now) {
            return;
        }

        if (!$this->lock()) {
            return; // Another process is currently updating this group.
        }

        $clients = $this->_serviceLocator->get('Database\Table\Clients');
        $groupInfo = $this->_serviceLocator->get('Database\Table\GroupInfo');
        $groupMemberships = $this->_serviceLocator->get('Database\Table\GroupMemberships');
        $config = $this->_serviceLocator->get('Model\Config');

        // Remove dynamic memberships where client no longer meets the criteria
        $groupMemberships->delete(
            array(
                'group_id' => $this['Id'],
                'static' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                "hardware_id NOT IN($this[DynamicMembersSql])"
            )
        );

        // Add dynamic memberships for clients which meet the criteria and don't
        // already have an entry in the cache (which might be dynamic, static or
        // excluded).
        $subquery = $groupMemberships->getSql()->select();
        $subquery->columns(array('hardware_id'))->where(array('group_id' => $this['Id']));
        $select = $clients->getSql()->select();
        $select->columns(
            array(
                'hardware_id' => 'id',
                'group_id' => new \Laminas\Db\Sql\Expression('?', $this['Id']),
                'static' => new \Laminas\Db\Sql\Literal(\Model\Client\Client::MEMBERSHIP_AUTOMATIC),
            )
        )->where(
            array(
                "id IN ($this[DynamicMembersSql])",
                new \Laminas\Db\Sql\Predicate\NotIn('id', $subquery),
            )
        );
        $groupMemberships->insert($select);

        // Update CacheCreationDate and CacheExpirationDate
        $minExpires = clone $now;
        $minExpires->modify(
            sprintf(
                '+%d seconds',
                $this->_serviceLocator->get('Library\Random')->getInteger(
                    0,
                    $config->groupCacheExpirationFuzz
                )
            )
        );
        $groupInfo->update(
            array(
                'create_time' => $now->getTimestamp(),
                'revalidate_from' => $minExpires->getTimestamp(),
            ),
            array('hardware_id' => $this['Id'])
        );

        $this->unlock();

        $minExpires->modify(sprintf('+%d seconds', $config->groupCacheExpirationInterval));
        $this->offsetSet('CacheCreationDate', $now);
        $this->offsetSet('CacheExpirationDate', $minExpires);
    }

    /**
     * Return names of all assigned packages
     *
     * @param string $direction one of [asc|desc]. Default: asc
     * @return string[]
     */
    public function getPackages($direction = 'asc')
    {
        $packages = $this->_serviceLocator->get('Database\Table\Packages');
        $select = $packages->getSql()->select();
        $select->columns(array('name'))
               ->join('devices', 'ivalue = fileid', array())
               ->where(
                   array(
                      'hardware_id' => $this['Id'],
                      'devices.name' => 'DOWNLOAD',
                   )
               )->order(array('download_available.name' => $direction));

        return array_column($packages->selectWith($select)->toArray(), 'name');
    }
}
