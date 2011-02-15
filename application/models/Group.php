<?php
/**
 * Class representing a group of computers
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * A group of computers
 *
 * Packages and settings assigned to a group will be assigned to all computers
 * which are a member of the group. There are 2 types of group membership:
 * dynamic membership is based on the result of an SQL query, while static
 * membership is assigned manually. Both types can be present within the same
 * group. It is also possible to exclude a computer from a group when it would
 * match the condition for dynamic membership.
 *
 * Properties:
 * - <b>Id:</b> primary key
 * - <b>Name:</b> display name
 * - <b>Description:</b> description
 * - <b>CreationDate:</b> timestamp of group creation
 * - <b>DynamicMembersSql:</b> SQL query for dynamic members, may be empty
 *
 *
 * The representation of groups in the database is a bit odd. Each group has a
 * row in the 'hardware' table with the 'deviceid' column set to
 * '_SYSTEMGROUP_'. Only the 'id', 'deviceid', 'name', 'lastdate' and
 * 'description' columns are used. For each group there is a corresponding row
 * in the 'groups' table. It is only relevant for dynamic membership. All fields
 * except hardware_id may be empty/NULL/0.
 *
 * - hardware_id: matches hardware.id.
 * - request: SQL query delivering 1 column with hardware IDs of group members.
 * - xmldef: alternate query definition. Not supported yet.
 * - create_time: UNIX timestamp of last cache computation, see below.
 * - revalidate_from: UNIX timestamp of next cache computation, see below.
 *
 *
 * The 'groups_cache' table contains membership information:
 *
 * - hardware_id: PK of computer
 * - group_id: PK of group
 * - static: 0 for cached dynamic membership, 1 for statically included, 2 for excluded
 *
 *
 * Class constants are defined for the 'static' value which should always be
 * used instead of literal integers. For future compatibility, always use the
 * '=' operator for comparision, not '!='.
 *
 * This class determines dynamic membership exclusively from the cache, so the
 * information might be out of date. This behavior is consistent with the
 * communication server. This class does not automatically rebuild the cache
 * when the expiration time has been reached as this would result in even more
 * database activity especially for short rebuild intervals. The communication
 * server will rebuild the cache upon next agent contact.
 * @package Models
 */
class Model_Group extends Model_ComputerOrGroup
{

    const MEMBERSHIP_DYNAMIC = 0;
    const MEMBERSHIP_STATIC = 1;
    const MEMBERSHIP_EXCLUDED = 2;

    /**
     * Property Map
     * @var array
     */
    protected $_propertyMap = array(
        // Values from 'hardware' table
        'Id' => 'id',
        'Name' => 'name',
        'CreationDate' => 'lastdate',
        'Description' => 'description',
        // Values from 'groups' table
        'DynamicMembersSql' => 'request',
    );

    /**
     * Non-text datatypes
     * @var array
     */
    protected $_types = array(
        'Id' => 'integer',
        'CreationDate' => 'timestamp',
    );


    /**
     * Return a statement object with all groups
     * @param array $columns Properties which should be returned. Default: all properties
     * @param integer $id If non-null, return only the group with the given ID. Default: all groups
     * @param string $order Logical property to sort by. Default: null
     * @param string $direction one of [asc|desc]. Default: asc
     * @return Zend_Db_Statement Query result
     */
    static function createStatementStatic(
        $columns=null,
        $id = null,
        $order=null,
        $direction='asc'
    )
    {
        $db = Zend_Registry::get('db');

        $dummy = new Model_Group;
        $map = $dummy->getPropertyMap();

        if (empty($columns)) {
            $columns = array_keys($map); // Select all properties
        }

        $joinGroupsTable = false;
        foreach ($columns as $column) {
            if ($column == 'DynamicMembersSql') {
                $joinGroupsTable = true;
            } else {
                if (isset($map[$column])) { // ignore nonexistent columns
                    $fromHardware[] = $map[$column];
                }
            }
        }

        // add PK if not already selected
        if (!in_array('id', $fromHardware)) {
            $fromHardware[] = 'id';
        }

        $select = $db->select()
            ->from('hardware', $fromHardware)
            ->order(self::getOrder($order, $direction, $map));

        if ($joinGroupsTable) {
            $select->join('groups', 'hardware.id = groups.hardware_id', 'request');
        } else {
            // Only return groups, not computers. Not necessary if the 'groups'
            // table has been joined since the join condition only matches
            // groups.
            $select->where("deviceid = '_SYSTEMGROUP_'");
        }

        if (!is_null($id)) {
            $select->where('id=?', $id);
        }

        return $select->query();
    }

    /**
     * Get a Model_Group object for the given primary key.
     * @param int $id Primary key
     * @return mixed Fully populated Model_Group object, FALSE if no group was found
     */
    static function fetchById($id)
    {
        return self::createStatementStatic(null, $id)->fetchObject('Model_Group');
    }

    /**
     * Return a statement object with names of all packages associated with this group
     * @param string $direction one of [asc|desc]. Default: asc
     * @return Zend_Db_Statement Query result
     */
    public function getPackages($direction='asc')
    {
        $db = Zend_Registry::get('db');

        $select = $db->select()
            ->from('devices', array())
            ->join(
                'download_enable',
                'devices.ivalue=download_enable.id',
                array()
            )
            ->join(
                'download_available',
                'download_enable.fileid=download_available.fileid',
                array('name')
            )
            ->where('hardware_id = ?', (int) $this->getId())
            ->where("devices.name='DOWNLOAD'")
            ->order(self::getOrder('Name', $direction, $this->_propertyMap));

        return $select->query();
    }

}
