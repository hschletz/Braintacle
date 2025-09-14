<?php

/**
 * A group of clients
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

namespace Model\Group;

use Database\Table\Clients;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use DateTimeInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Literal;
use Laminas\Db\Sql\Predicate\NotIn;
use Laminas\Db\Sql\Sql;
use Model\Client\Client;
use Model\Client\ClientManager;
use Model\Config;
use Psr\Clock\ClockInterface;
use Random\Randomizer;
use Throwable;

/**
 * A group of clients
 *
 * Packages and settings assigned to a group apply to all members. Clients can
 * become a member by manual assignment or automatically based on the result of
 * a query. It is also possible to unconditionally exclude a client from a group
 * regardless of query result.
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property DateTimeInterface $creationDate
 * @property string $dynamicMembersSql SQL query for dynamic members, may be empty
 * @property DateTimeInterface $cacheCreationDate Timestamp of last cache update
 * @property DateTimeInterface $cacheExpirationDate Timestamp when cache will expire and get rebuilt
 *
 * @psalm-suppress PossiblyUnusedProperty -- referenced in template
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
        $members = $this->container->get(ClientManager::class)->getClients(
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
            $sql = new Sql($this->container->get(Adapter::class));
            $query = $sql->buildSqlString($members);
            $this->container->get(GroupInfo::class)->update(
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
            try {
                // Get list of existing memberships
                $existingMemberships = [];
                $groupMemberships = $this->container->get(GroupMemberships::class);
                $select = $groupMemberships->getSql()->select();
                $select->columns(['hardware_id', 'static'])->where(['group_id' => $id]);
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
                                    ['static' => $type],
                                    ['group_id' => $id, 'hardware_id' => $member],
                                );
                            }
                        } else {
                            $groupMemberships->insert(
                                ['group_id' => $id, 'hardware_id' => $member, 'static' => $type]
                            );
                        }
                    }
                    $connection->commit();
                } catch (Throwable $throwable) {
                    $connection->rollBack();
                    throw $throwable;
                }
            } finally {
                $this->unlock();
            }
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

        $now = $this->container->get(ClockInterface::class)->now();
        // Do nothing if cache has not expired yet and update is not forced.
        if (!$force and $this['CacheExpirationDate'] > $now) {
            return;
        }

        if (!$this->lock()) {
            return; // Another process is currently updating this group.
        }

        try {
            $clients = $this->container->get(Clients::class);
            $groupInfo = $this->container->get(GroupInfo::class);
            $groupMemberships = $this->container->get(GroupMemberships::class);
            $config = $this->container->get(Config::class);

            // Remove dynamic memberships where client no longer meets the criteria
            $groupMemberships->delete([
                'group_id' => $this['Id'],
                'static' => Client::MEMBERSHIP_AUTOMATIC,
                "hardware_id NOT IN({$this['DynamicMembersSql']})",
            ]);

            // Add dynamic memberships for clients which meet the criteria and don't
            // already have an entry in the cache (which might be dynamic, static or
            // excluded).
            $subquery = $groupMemberships->getSql()->select();
            $subquery->columns(['hardware_id'])->where(['group_id' => $this['Id']]);
            $select = $clients->getSql()->select();
            $select->columns([
                'hardware_id' => 'id',
                'group_id' => new Expression('?', $this['Id']),
                'static' => new Literal((string) Client::MEMBERSHIP_AUTOMATIC),
            ])->where([
                "id IN ($this[DynamicMembersSql])",
                new NotIn('id', $subquery),
            ]);
            $groupMemberships->insert($select);

            // Update CacheCreationDate and CacheExpirationDate
            $minExpires = $now->modify(
                sprintf(
                    '+%d seconds',
                    $this->container->get(Randomizer::class)->getInt(0, $config->groupCacheExpirationFuzz)
                )
            );
            $groupInfo->update(
                [
                    'create_time' => $now->getTimestamp(),
                    'revalidate_from' => $minExpires->getTimestamp(),
                ],
                ['hardware_id' => $this['Id']],
            );
        } finally {
            $this->unlock();
        }

        $this->offsetSet('CacheCreationDate', $now);
        $this->offsetSet(
            'CacheExpirationDate',
            $minExpires->modify(
                sprintf('+%d seconds', $config->groupCacheExpirationInterval)
            )
        );
    }
}
