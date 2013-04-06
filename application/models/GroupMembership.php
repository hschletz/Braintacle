<?php
/**
 * Class representing a group membership
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
class Model_GroupMembership extends Model_Abstract
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

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from query result
        'GroupId' => 'group_id',
        'GroupName' => 'name',
        'Membership' => 'static',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Membership' => 'enum',
    );

    /**
     * Return a statement object with all all group memberships matching criteria.
     * @param integer $computer ID of computer for which to determine memberships
     * @param integer $membership Type of membership to determine, default: all types
     * @param string $order Property to sort by, default: Group
     * @param string $direction Direction for sorting, default: ascending
     * @return Zend_Db_Statement
     */
    static function createStatementStatic(
        $computer,
        $membership=self::TYPE_ALL,
        $order='GroupName',
        $direction='asc'
    )
    {
        Model_Group::updateAll();

        $db = Model_Database::getAdapter();

        $dummy = new Model_GroupMembership;
        $map = $dummy->getPropertyMap();
        $order = self::getOrder($order, $direction, $map);

        $select = $db->select()
            ->from('groups_cache', array('group_id', 'static'))
            ->join(
                'hardware',
                'groups_cache.group_id=hardware.id',
                array('name')
            )
            ->where('hardware_id=?', $computer)
            ->order($order);

        if (!is_null($membership)) {
            switch ($membership) {
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
                    $select->where('static=?', $membership);
                    break;
                default:
                    throw new UnexpectedValueException(
                        "Bad value for membership: $membership"
                    );
            }
        }

        return $select->query();
    }

}
