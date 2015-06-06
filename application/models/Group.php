<?php
/**
 * Class representing a group of computers
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
 *
 * - <b>Id:</b> primary key
 * - <b>Name:</b> display name
 * - <b>Description:</b> description
 * - <b>CreationDate:</b> timestamp of group creation
 * - <b>DynamicMembersSql:</b> SQL query for dynamic members, may be empty
 * - <b>DynamicMembersXml:</b> XML definition for dynamic members (not supported yet), may be empty
 * - <b>CacheCreationDate:</b> Timestamp of last cache computation
 * - <b>CacheExpirationDate:</b> Timestamp when cache will expire and be rebuilt
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
 * - revalidate_from: create_time + random offset, see below
 *
 *
 * The 'groups_cache' table contains membership information:
 *
 * - hardware_id: PK of computer
 * - group_id: PK of group
 * - static: 0 for cached dynamic membership, 1 for statically included, 2 for excluded
 *
 *
 * Dynamic membership is determined exclusively from the cache, so the
 * information might be out of date. Any method that accesses the groups_cache
 * table directly should call {@link update()} before doing that.
 *
 * Two config variables control how often the cache gets rebuilt.
 * <i>GroupCacheExpirationInterval</i> is the minimum number of seconds between
 * rebuilds for a particular group. To prevent recomputation of all groups at
 * once (which may be a resource-intensive process), a random number of seconds
 * between 0 and <i>GroupCacheExpirationFuzz</i> is added.
 * @package Models
 */
class Model_Group extends \Model_Abstract
{

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
        'DynamicMembersXml' => 'xmldef',
        'CacheExpirationDate' => 'revalidate_from', // Will be mangled by {@link getProperty()}
        'CacheCreationDate' => 'create_time',
    );

    /**
     * Non-text datatypes
     * @var array
     */
    protected $_types = array(
        'Id' => 'integer',
        'CreationDate' => 'timestamp',
        'CacheExpirationDate' => 'timestamp',
        'CacheCreationDate' => 'timestamp',
    );

    /**
     * Global cache for getDefaultConfig() results
     *
     * This is a 2-dimensional array: $_configDefault[group ID][option name] = value
     */
    protected static $_configDefault = array();

    /**
     * Retrieve a property by its logical name
     *
     * CacheExpirationDate and CacheCreationDate are automatically converted to
     * a Zend_Date object unless $rawValue is true. Additionally, the value of
     * the global GroupCacheExpirationInterval Option is added to
     * CacheExpirationDate, so that the real expiration date is returned instead
     * of the value in the database (which is CacheCreationDate + random offset)
     * NULL is returned for these Properties if they have not been initialized.
     */
    public function getProperty($property, $rawValue=false)
    {
        if (!$rawValue and ($property == 'CacheExpirationDate' or $property == 'CacheCreationDate')) {
            $value = parent::getProperty($property, true);
            if ($value == 0) {
                $value = null;
            } else {
                $value = new Zend_Date(
                    $value,
                    Zend_Date::TIMESTAMP
                );
                if ($property == 'CacheExpirationDate') {
                    $value->addSecond($this->_config->groupCacheExpirationInterval);
                }
            }
        } else {
            $value = parent::getProperty($property, $rawValue);
        }

        return $value;
    }

    /**
     * Set a property by its logical name.
     *
     * For CacheCreationDate and CacheExpirationDate, a Zend_Date object is
     * expected, which will be processed to match the internal representation.
     *
     * For DynamicMembersSql, the value is written to the database if it is
     * valid - it must be a Zend_Db_Select object for security reasons, and it
     * must deliver exactly 1 column.
     *
     * @throws InvalidArgumentException if DynamicMembersSql value is not a Zend_Db_Select object
     * @throws LogicException if DynamicMembersSql does not have exactly 1 column
     */
    public function setProperty($property, $value)
    {
        $columnName = $this->getColumnName($property);
        switch ($property) {
            case 'CacheExpirationDate':
                // Create new object to leave original object untouched
                $value = new Zend_Date($value);
                $value->subSecond($this->_config->groupCacheExpirationInterval);
                $this->__set($columnName, $value->get(Zend_Date::TIMESTAMP));
                break;
            case 'CacheCreationDate':
                $this->__set($columnName, $value->get(Zend_Date::TIMESTAMP));
                break;
            case 'DynamicMembersSql':
                // Check SQL syntax and number of columns if possible
                if ($value instanceof Zend_Db_Select) {
                    $numCols = count($value->getPart(Zend_Db_Select::COLUMNS));
                    if ($numCols != 1) {
                        throw new LogicException(
                            'DynamicMembersSql: expected 1 column, got ' . $numCols
                        );
                    }
                } else {
                    throw new InvalidArgumentException(
                        'DynamicMembersSql: invalid datatype'
                    );
                }

                $value = (string) $value;
                parent::setProperty($property, $value); // Update internal state
                Model_Database::getAdapter()->update(
                    'groups',
                    array('request' => $value),
                    array('hardware_id = ?' => $this->getId())
                );
                $this->update(true); // Force cache update
                break;
            default:
                parent::setProperty($property, $value);
        }
    }

    /**
     * Return names of all packages assigned to this group
     *
     * @param string $direction one of [asc|desc]. Default: asc
     * @return string[]
     */
    public function getPackages($direction='asc')
    {
        $db = Model_Database::getAdapter();

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

        return $select->query()->fetchAll(\Zend_Db::FETCH_COLUMN);
    }

    /**
     * Set group members based on query
     *
     * If $type is \Model_GroupMembership::TYPE_DYNAMIC, the DynamicMembersSql
     * property will be set to the resulting query. For other values, the query
     * will be executed and $type is stored as manual membership/exclusion on
     * the results.
     *
     * @param integer $type Membership type
     * @param string|array $filter Name or array of names of a pre-defined filter routine
     * @param string|array $search Search parameter(s) passed to the filter. May be case sensitive depending on DBMS.
     * @param string|array $operator Comparision operator
     * @param bool|array $invert Invert query results (return all computers NOT matching criteria)
     */
    public function setMembersFromQuery($type, $filter, $search, $operator, $invert)
    {
        $computers = \Library\Application::getService('Model\Computer\Computer')->fetch(
            array('Id'),
            null,
            null,
            $filter,
            $search,
            $operator,
            $invert,
            false,
            true,
            ($type != \Model_GroupMembership::TYPE_DYNAMIC)
        );
        if ($type == \Model_GroupMembership::TYPE_DYNAMIC) {
            $this->setProperty('DynamicMembersSql', $computers);
            return;
        }

        // Wait until lock can be obtained
        while (!$this->lock()) {
            sleep(1);
        }

        $db = Model_Database::getAdapter();
        $id = $this->getId();

        // Get list of existing memberships.
        $memberships = $db->fetchPairs(
            'SELECT hardware_id, static FROM groups_cache WHERE group_id = ?',
            array ($id)
        );

        $db->beginTransaction();
        try {
            foreach ($computers as $computer) {
                if ($computer instanceof Model_Computer) {
                    $computer = $computer->getId();
                }
                if (isset($memberships[$computer])) {
                    // Update only memberships of a different type
                    if ($memberships[$computer] != $type) {
                        $db->update(
                            'groups_cache',
                            array('static' => $type),
                            array(
                                'group_id = ?' => $id,
                                'hardware_id = ?' => $computer
                            )
                        );
                    }
                } else {
                    $db->insert(
                        'groups_cache',
                        array(
                            'group_id' => $id,
                            'hardware_id' => $computer,
                            'static' => $type
                        )
                    );
                }
            }
            $db->commit();
        } catch (Exception $exception) {
            $db->rollBack();
            $this->unlock();
            throw $exception;
        }
        $this->unlock();
    }

    /**
     * Update the cache for dynamic members
     *
     * Dynamic members are always determined from the cache. This method updates
     * the cache for this particular group. By default, the cache is not updated
     * before its expiration time has been reached. This method will do nothing
     * in that case. Set $force to TRUE to rebuild the cache in any case.
     * @param bool $force Always rebuild cache, regardless of expiration time.
     */
    public function update($force = false)
    {
        $criteria = $this->getDynamicMembersSql();
        if (!$criteria) {
            return; // Nothing to do if no SQL query defined for this group
        }

        $expires = $this->getCacheExpirationDate();
        $currentTime = Zend_Date::now();

        // Do nothing if expiration time has not been reached and $force is false.
        if ($expires and !$force and ($expires->compare($currentTime) == 1)) {
            return;
        }

        if ($this->getDynamicMembersXml()) {
            throw new RuntimeException('XML group definition not supported yet');
        }

        if (!$this->lock()) {
            return; // Another process is currently updating this group.
        }

        $db = Model_Database::getAdapter();

        // Delete computers from the cache which no longer meet the criteria
        $db->delete(
            'groups_cache',
            array(
                'group_id = ?' => $this->getId(),
                'static = ?' => Model_GroupMembership::TYPE_DYNAMIC,
                "hardware_id NOT IN ($criteria)" => null
            )
        );

        // Insert computers which meet the criteria and don't already have an
        // entry in the cache (which might be dynamic, static or excluded).
        // Also filter group IDs from criteria, i.e. only computers will show up
        // in the cache.
        $newIds = $db
            ->select()
            ->from('hardware', array('id'))
            ->where("id IN ($criteria)")
            ->where(
                'id NOT IN (SELECT hardware_id FROM groups_cache WHERE group_id=?)',
                $this->getId()
            )
            ->where('id NOT IN (SELECT hardware_id FROM groups)')
            ->query();
        while ($computer = $newIds->fetchColumn()) {
            $db->insert(
                'groups_cache',
                array(
                    'group_id' => $this->getId(),
                    'hardware_id' => $computer,
                    'static' => Model_GroupMembership::TYPE_DYNAMIC
                )
            );
        }

        // Update CacheCreationDate and CacheExpirationDate in the database
        $fuzz = mt_rand(0, $this->_config->groupCacheExpirationFuzz);
        $minExpires = new Zend_Date($currentTime);
        $minExpires->addSecond($fuzz);

        $db->update(
            'groups',
            array(
                'create_time' => $currentTime->get(Zend_Date::TIMESTAMP),
                'revalidate_from' => $minExpires->get(Zend_Date::TIMESTAMP)
            ),
            array('hardware_id = ?' => $this->getId())
        );

        $this->unlock();

        // Update CacheCreationDate and CacheExpirationDate properties
        $this->setProperty('CacheCreationDate', $currentTime);
        // Do not use setProperty() here to avoid unnecessary calculations.
        $this->__set('revalidate_from', $minExpires->get(Zend_Date::TIMESTAMP));
    }

    /** {@inheritdoc} */
    public function getDefaultConfig($option)
    {
        $id = $this->getId();
        if (isset(self::$_configDefault[$id]) and array_key_exists($option, self::$_configDefault[$id])) {
            return self::$_configDefault[$id][$option];
        }

        if ($option == 'allowScan') {
            if ($this->_config->scannersPerSubnet == 0) {
                $value = 0;
            } else {
                $value = 1;
            }
        } else {
            $value = $this->_config->$option;
        }

        self::$_configDefault[$id][$option] = $value;
        return $value;
    }
}
