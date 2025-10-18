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

use Braintacle\Group\Group;
use Braintacle\Locks;
use Database\Table\ClientConfig;
use Database\Table\ClientsAndGroups;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use Laminas\Db\Adapter\Adapter;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Group manager
 */
class GroupManager
{
    public function __construct(private ContainerInterface $container) {}

    /**
     * Delete a group
     *
     * @throws \Model\Group\RuntimeException if group is locked
     */
    public function deleteGroup(Group $group)
    {
        /** @var Locks */
        $locks = $this->container->get(Locks::class);
        if (!$locks->lock($group)) {
            throw new RuntimeException('Cannot delete group because it is locked');
        }

        try {
            $id = $group->id;
            $connection = $this->container->get(Adapter::class)->getDriver()->getConnection();
            $connection->beginTransaction();
            try {
                $this->container->get(GroupMemberships::class)->delete(['group_id' => $id]);
                $this->container->get(ClientConfig::class)->delete(['hardware_id' => $id]);
                $this->container->get(GroupInfo::class)->delete(['hardware_id' => $id]);
                $this->container->get(ClientsAndGroups::class)->delete(['id' => $id]);
                $connection->commit();
            } catch (Throwable $throwable) {
                $connection->rollBack();
                throw $throwable;
            }
        } finally {
            $locks->release($group);
        }
    }
}
