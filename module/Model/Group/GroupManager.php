<?php

/**
 * Group manager
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

use Countable;
use Database\Table\ClientConfig;
use Database\Table\ClientsAndGroups;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use Laminas\Db\Adapter\Adapter;
use Model\Config;
use Nada\Database\AbstractDatabase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

/**
 * Group manager
 */
class GroupManager
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Return a all groups matching criteria
     *
     * @param string $filter Optional filter to apply (Id|Name|Expired|Member), default: return all groups
     * @param mixed $filterArg Argument for Id, Name and Member filters, ignored otherwise
     * @param string $order Property to sort by. Default: none
     * @param string $direction one of [asc|desc]. Default: asc
     * @return iterable<Group>|Countable
     */
    public function getGroups($filter = null, $filterArg = null, $order = null, $direction = 'asc'): iterable
    {
        /** @var GroupInfo */
        $groupInfo = $this->container->get('Database\Table\GroupInfo');
        $select = $groupInfo->getSql()->select();
        $select->columns(array('request', 'create_time', 'revalidate_from'))
            ->join(
                'hardware',
                'hardware.id = groups.hardware_id',
                array('id', 'name', 'lastdate', 'description'),
                \Laminas\Db\Sql\Select::JOIN_INNER
            );

        switch ($filter) {
            case null:
                break;
            case 'Id':
                $select->where(array('id' => $filterArg));
                break;
            case 'Name':
                $select->where(array('name' => $filterArg));
                break;
            case 'Expired':
                $now = $this->container->get(ClockInterface::class)->now()->getTimestamp();
                $select->where(
                    new \Laminas\Db\Sql\Predicate\Operator(
                        'revalidate_from',
                        '<=',
                        $now - $this->container->get(Config::class)->groupCacheExpirationInterval
                    )
                );
                break;
            case 'Member':
                $this->updateCache();
                $select->join('groups_cache', 'groups_cache.group_id = groups.hardware_id', array());
                $select->where(
                    array(
                        'groups_cache.hardware_id' => $filterArg,
                        new \Laminas\Db\Sql\Predicate\Operator('static', '!=', \Model\Client\Client::MEMBERSHIP_NEVER),
                    )
                );
                break;
            default:
                throw new \InvalidArgumentException(
                    'Invalid group filter: ' . $filter
                );
                break;
        }

        if ($order) {
            $select->order(array($groupInfo->getHydrator()->extractName($order) => $direction));
        }

        return $groupInfo->selectWith($select);
    }

    /**
     * Get group with given name.
     *
     * @param string $name Group name
     * @return \Model\Group\Group
     * @throws \RuntimeException if the given group name does not exist
     * @throws \InvalidArgumentException if $name is empty
     */
    public function getGroup($name)
    {
        if ($name == '') {
            throw new \InvalidArgumentException('No group name given');
        }
        $result = [...$this->getGroups('Name', $name)];
        if (!$result) {
            throw new \RuntimeException('Unknown group name: ' . $name);
        }
        return $result[0];
    }

    /**
     * Create a new group
     *
     * @param string $name Group name, must not exist yet.
     * @param string $description Optional description, default: NULL.
     * @throws \InvalidArgumentException if group name is empty
     * @throws \RuntimeException if a group with the given name already exists
     **/
    public function createGroup($name, $description = null)
    {
        if ($name == '') {
            throw new \InvalidArgumentException('Group name is empty');
        }

        $clientsAndGroups = $this->container->get(ClientsAndGroups::class);
        if ($clientsAndGroups->select(array('name' => $name, 'deviceid' => '_SYSTEMGROUP_'))->count()) {
            throw new \RuntimeException('Group already exists: ' . $name);
        }

        if ($description == '') {
            $description = null;
        }
        $now = $this->container->get(ClockInterface::class)->now();

        $connection = $this->container->get(Adapter::class)->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $clientsAndGroups->insert(
                array(
                    'name' => $name,
                    'description' => $description,
                    'deviceid' => '_SYSTEMGROUP_',
                    'lastdate' => $now->format($this->container->get(AbstractDatabase::class)->timestampFormatPhp()),
                )
            );
            $id = $clientsAndGroups->select(array('name' => $name, 'deviceid' => '_SYSTEMGROUP_'))->current()['id'];
            $this->container->get(GroupInfo::class)->insert(
                array(
                    'hardware_id' => $id,
                    'create_time' => $now->getTimestamp(),
                )
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }

    /**
     * Delete a group
     *
     * @param \Model\Group\Group $group
     * @throws \Model\Group\RuntimeException if group is locked
     */
    public function deleteGroup(\Model\Group\Group $group)
    {
        if (!$group->lock()) {
            throw new RuntimeException('Cannot delete group because it is locked');
        }

        $id = $group['Id'];
        $connection = $this->container->get(Adapter::class)->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $this->container->get(GroupMemberships::class)->delete(array('group_id' => $id));
            $this->container->get(ClientConfig::class)->delete(array('hardware_id' => $id));
            $this->container->get(GroupInfo::class)->delete(array('hardware_id' => $id));
            $this->container->get(ClientsAndGroups::class)->delete(array('id' => $id));
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $group->unlock();
            throw $e;
        }
        $group->unlock();
    }

    /**
     * Update the membership cache for all expired groups
     */
    public function updateCache()
    {
        foreach ($this->getGroups('Expired') as $group) {
            $group->update(true);
        }
    }
}
