<?php
/**
 * Class representing a group membership
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
 * Group membership
 *
 * Properties:
 *
 * - <b>GroupId</b> Group ID
 * - <b>GroupName</b> Group name
 * - <b>Membership</b> Membership type - one of {@link TYPE_DYNAMIC},
 * {@link TYPE_STATIC} or {@link TYPE_EXCLUDED}.
 *
 *
 * Always use the class constants for membership types instead of plain
 * integers, and treat them as enum-style values, i.e. don't perform
 * comparisions other than '==' or '===' on them. The actual range of values is
 * handled internally and might change in the future.
 * There is an additional constant {@link TYPE_INCLUDED} which refers to both
 * dynamic and static membership. It is used only for method calls like
 * {@link createStatementStatic()} and will never be returned as a property.
 * @package Models
 */
class Model_GroupMembership extends \ArrayObject
{

    // Class constants describing membership types.
    // The first three map to the value of groups_cache.static
    const TYPE_DYNAMIC = 0;
    const TYPE_STATIC = 1;
    const TYPE_EXCLUDED = 2;
    // The next one is not present in groups_cache.static.
    // It refers to both dynamically or statically included.
    const TYPE_INCLUDED = -1;
    // The next one is not present in groups_cache.static.
    // It refers to both statically included or excluded.
    const TYPE_MANUAL = -2;
    // The next one is not present in groups_cache.static.
    // It refers to all membership types.
    const TYPE_ALL = -3;

    /**
     * Return all group memberships for given computer and type
     *
     * @param \Model_Computer $computer Computer for which to determine memberships
     * @param integer $membershipType Type of membership to determine,
     * @param string $order Property to sort by, default: GroupName
     * @param string $direction Direction for sorting, default: ascending
     * @return \Model_GroupMembership[]
     */
    public function fetch(
        $computer,
        $membershipType,
        $order='GroupName',
        $direction='asc'
    )
    {
        \Library\Application::getService('Model\Group\GroupManager')->updateCache();

        $db = Model_Database::getAdapter();

        $select = $db->select()
            ->from('groups_cache', array('group_id', 'static'))
            ->join(
                'hardware',
                'groups_cache.group_id=hardware.id',
                array('name')
            )
            ->where('hardware_id=?', $computer['Id']);

        if (!is_null($membershipType)) {
            switch ($membershipType) {
                case self::TYPE_ALL:
                    break;
                case self::TYPE_INCLUDED:
                    $select->where(
                        'static IN(?)',
                        array(self::TYPE_DYNAMIC, self::TYPE_STATIC)
                    );
                    break;
                case self::TYPE_MANUAL:
                    $select->where(
                        'static IN(?)',
                        array(self::TYPE_STATIC, self::TYPE_EXCLUDED)
                    );
                    break;
                case self::TYPE_DYNAMIC:
                case self::TYPE_STATIC:
                case self::TYPE_EXCLUDED:
                    $select->where('static=?', $membershipType);
                    break;
                default:
                    throw new UnexpectedValueException(
                        "Bad value for membership: $membershipType"
                    );
            }
        }

        if ($order) {
            $propertyMap = array(
                'GroupId' => 'group_id',
                'GroupName' => 'name',
                'Membership' => 'static',
            );
            if (isset($propertyMap[$order])) {
                $order = $propertyMap[$order];
            } elseif ($order != 'id') {
                throw new \UnexpectedValueException('Unknown property: ' . $order);
            }
            if ($direction) {
                $order .= ' ' . $direction;
            }
            $select->order($order);
        }

        $statement = $select->query();
        $statement->setFetchMode(\Zend_Db::FETCH_ASSOC);
        $result = array();
        foreach ($statement as $row) {
            $result[] = new \Model_GroupMembership(
                array(
                    'GroupId' => $row['group_id'],
                    'GroupName' => $row['name'],
                    'Membership' => $row['static']
                )
            );
        }
        return $result;
    }

}
