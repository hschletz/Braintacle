<?php

/**
 * Group manager
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

use Countable;
use Database\Table\GroupInfo;

/**
 * Group manager
 */
class GroupManager
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
        $groupInfo = $this->_serviceManager->get('Database\Table\GroupInfo');
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
                $now = $this->_serviceManager->get('Library\Now')->getTimestamp();
                $select->where(
                    new \Laminas\Db\Sql\Predicate\Operator(
                        'revalidate_from',
                        '<=',
                        $now - $this->_serviceManager->get('Model\Config')->groupCacheExpirationInterval
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

        $clientsAndGroups = $this->_serviceManager->get('Database\Table\ClientsAndGroups');
        if ($clientsAndGroups->select(array('name' => $name, 'deviceid' => '_SYSTEMGROUP_'))->count()) {
            throw new \RuntimeException('Group already exists: ' . $name);
        }

        if ($description == '') {
            $description = null;
        }
        $now = $this->_serviceManager->get('Library\Now');

        $connection = $this->_serviceManager->get('Db')->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $clientsAndGroups->insert(
                array(
                    'name' => $name,
                    'description' => $description,
                    'deviceid' => '_SYSTEMGROUP_',
                    'lastdate' => $now->format($this->_serviceManager->get('Database\Nada')->timestampFormatPhp()),
                )
            );
            $id = $clientsAndGroups->select(array('name' => $name, 'deviceid' => '_SYSTEMGROUP_'))->current()['id'];
            $this->_serviceManager->get('Database\Table\GroupInfo')->insert(
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
        $connection = $this->_serviceManager->get('Db')->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $this->_serviceManager->get('Database\Table\GroupMemberships')->delete(array('group_id' => $id));
            $this->_serviceManager->get('Database\Table\ClientConfig')->delete(array('hardware_id' => $id));
            $this->_serviceManager->get('Database\Table\GroupInfo')->delete(array('hardware_id' => $id));
            $this->_serviceManager->get('Database\Table\ClientsAndGroups')->delete(array('id' => $id));
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
