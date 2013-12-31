<?php
/**
 * Class for managing duplicate computers
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
 */

namespace Model\Computer;

use Database\Table;

/**
 * Class for managing duplicate computers
 *
 * Duplicates are computers with more than one entry in the database. These occur
 * when the client's OS is reinstalled and previous agent configuration is lost
 * so that a new client ID is generated. Agent bugs and misconfiguration can
 * cause duplicates, too.
 *
 * This class provides methods to identify duplicates based on various criteria
 * which are supposed to be unique:
 *
 * - Computer name. It is possible to have multiple computers with the same
 *   name, but this is bad and it is recommended to rename affected computers.
 * - MAC address. These are not strictly bound to computers, but to network
 *   interfaces, and can sometimes move to a different computer. Some virtual
 *   interfaces (like PPP or VPN adapters) may also have non-unique MAC
 *   addresses. For this reason, particular MAC addresses can be blacklisted via
 *   allow() to prevent them from being used as duplicate criteria.
 * - Serial. Usually hardcoded in the computer's BIOS/UEFI configuration. Some
 *   products may have no or non-unique serials which can be blacklisted via
 *   allow().
 * - Asset tag. Usually hardcoded or user provided in the computer's BIOS/UEFI
 *   configuration. May be non-unique and therefore  get blacklisted via
 *   allow().
 *
 * Once identified, duplicates can be eliminated via merge().
 */
class Duplicates
{
    /**
     * ComputersAndGroups prototype
     * @var \Database\Table\ComputersAndGroups
     */
    protected $_computersAndGroups;

    /**
     * ComputerSystemInfo prototype
     * @var \Database\Table\ComputerSystemInfo
     */
    protected $_computerSystemInfo;

    /**
     * NetworkInterfaces prototype
     * @var \Database\Table\NetworkInterfaces
     */
    protected $_networkInterfaces;

    /**
     * DuplicateAssetTags prototype
     * @var \Database\Table\DuplicateAssetTags
     */
    protected $_duplicateAssetTags;

    /**
     * DuplicateSerials prototype
     * @var \Database\Table\DuplicateSerials
     */
    protected $_duplicateSerials;

    /**
     * DuplicateMacAddresses prototype
     * @var \Database\Table\DuplicateMacAddresses
     */
    protected $_duplicateMacaddresses;

    /**
     * ItemConfig prototype
     * @var \Database\Table\ItemConfig
     */
    protected $_itemConfig;

    /**
     * Computer prototype
     * @var \Model_Computer
     */
    protected $_computer;

    /**
     * Constructor
     *
     * @param \Database\Table\ComputersAndGroups ComputersAndGroups prototype
     * @param \Database\Table\ComputerSystemInfo ComputerSystemInfo prototype
     * @param \Database\Table\NetworkInterfaces NetworkInterfaces prototype
     * @param \Database\Table\DuplicateAssetTags DuplicateAssetTags prototype
     * @param \Database\Table\DuplicateSerials DuplicateSerials prototype
     * @param \Database\Table\DuplicateMacAddresses DuplicateMacAddresses prototype
     * @param \Database\Table\ItemConfig ItemConfig prototype
     * @param \Model_Computer \Model_Computer prototype
     */
    public function __construct(
        Table\ComputersAndGroups $computersAndGroups,
        Table\ComputerSystemInfo $computerSystemInfo,
        Table\NetworkInterfaces $networkInterfaces,
        Table\DuplicateAssetTags $duplicateAssetTags,
        Table\DuplicateSerials $duplicateSerials,
        Table\DuplicateMacAddresses $duplicateMacAddresses,
        Table\ItemConfig $itemConfig,
        \Model_Computer $computer
    )
    {
        $this->_computersAndGroups = $computersAndGroups;
        $this->_computerSystemInfo = $computerSystemInfo;
        $this->_networkInterfaces = $networkInterfaces;
        $this->_duplicateAssetTags = $duplicateAssetTags;
        $this->_duplicateSerials = $duplicateSerials;
        $this->_duplicateMacaddresses = $duplicateMacAddresses;
        $this->_itemConfig = $itemConfig;
        $this->_computer = $computer;
    }

    /**
     * Get query for duplicate values of given griteria
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @return \Zend\Db\Sql\Select
     * @throws \InvalidArgumentException if $criteria is invalid
     */
    protected function _getDuplicateValues($criteria)
    {
        switch ($criteria) {
            case 'Name':
                $table = $this->_computersAndGroups;
                $column = 'name';
                $where = 'deviceid NOT IN(\'_SYSTEMGROUP_\', \'_DOWNLOADGROUP_\')';
                break;
            case 'AssetTag':
                $table = $this->_computerSystemInfo;
                $column = 'assettag';
                $where = 'assettag NOT IN(SELECT assettag FROM braintacle_blacklist_assettags)';
                break;
            case 'Serial':
                $table = $this->_computerSystemInfo;
                $column = 'ssn';
                $where = 'ssn NOT IN(SELECT serial FROM blacklist_serials)';
                break;
            case 'MacAddress':
                $table = $this->_networkInterfaces;
                $column = 'macaddr';
                $where = 'macaddr NOT IN(SELECT macaddress FROM blacklist_macaddresses)';
                break;
            default:
                throw new \InvalidArgumentException('Invalid criteria: ' . $criteria);
        }
        $select = $table->getSql()->select();
        $select->columns(array($column))
               ->where($where)
               ->group($column)
               ->having("COUNT($column) > 1");
        return $select;
    }

    /**
     * Get number of duplicates with given criteria
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @return integer Number of duplicates
     */
    public function count($criteria)
    {
        $subQuery = $this->_getDuplicateValues($criteria);
        $columns = $subQuery->getRawState($subQuery::COLUMNS);
        $column = $columns[0];
        switch ($criteria) {
            case 'Name':
                $table = $this->_computersAndGroups;
                break;
            case 'AssetTag':
            case 'Serial':
                $table = $this->_computerSystemInfo;
                break;
            case 'MacAddress':
                $table = $this->_networkInterfaces;
                break;
        }
        $subQuery = $subQuery->getSqlString($this->_computersAndGroups->getAdapter()->getPlatform());

        $sql = $table->getSql();
        $select = $sql->select();
        $select->columns(
            array(
                'num_computers' => new \Zend\Db\Sql\Expression("COUNT($column)")
            )
        );
        $select->where("$column IN($subQuery)");
        if ($table instanceof Table\ComputersAndGroups) {
            $select->where('deviceid NOT IN(\'_SYSTEMGROUP_\', \'_DOWNLOADGROUP_\')');
        }
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return $row['num_computers'];
    }

    /**
     * Retrieve duplicate computers with given criteria
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @param string $order Sorting order (default: 'Id')
     * @param string $direction One of asc|desc (default: 'asc')
     * @return \Zend\Db\ResultSet\AbstractResultSet \Model_Computer iterator
     */
    public function find($criteria, $order='Id', $direction='asc')
    {
        $subQuery = $this->_getDuplicateValues($criteria);
        $table = $subQuery->getRawState($subQuery::TABLE);
        $columns = $subQuery->getRawState($subQuery::COLUMNS);
        $column = $columns[0];
        $subQuery = $subQuery->getSqlString($this->_computersAndGroups->getAdapter()->getPlatform());

        $sql = $this->_computersAndGroups->getSql();
        $select = $sql->select();
        $select->columns(array('id', 'name', 'lastcome'));
        $select->join(
            'networks',
            'networks.hardware_id = hardware.id',
            array('networkinterface_macaddress' => 'macaddr'),
            $select::JOIN_LEFT
        )
        ->join(
            'bios',
            'bios.hardware_id=hardware.id',
            array('ssn', 'assettag'),
            $select::JOIN_LEFT
        )
        ->where("$column IN($subQuery)");
        if ($table == 'hardware') {
            $select->where('deviceid NOT IN(\'_SYSTEMGROUP_\', \'_DOWNLOADGROUP_\')');
        }
        $select->order(\Model_Computer::getOrder($order, $direction, $this->_computer->getPropertyMap()));

        $resultSet = new \Zend\Db\ResultSet\HydratingResultSet(
            new \Zend\Stdlib\Hydrator\ArraySerializable,
            $this->_computer
        );
        $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());
        return $resultSet;
    }

    /**
     * Merge computers
     *
     * This method is used to eliminate duplicates in the database. Based on the
     * last contact, the newest entry is preserved. All older entries are
     * deleted. Some information from the older entries can be preserved on the
     * remaining computer.
     *
     * @param integer[] $computers IDs of computers to merge
     * @param bool $mergeCustomFields Preserve custom fields from oldest computer
     * @param bool $mergeGroups Preserve manual group assignments from old computers
     * @param bool $mergePackages Preserve package assignments from old computers missing on new computer
     * @throws \RuntimeException if an affected computer cannot be locked
     */
    public function merge(array $computers, $mergeCustomFields, $mergeGroups, $mergePackages)
    {
        // Remove duplicate IDs
        $computers = array_unique($computers);
        if (count($computers) < 2) {
            return; // Nothing to do
        }

        $connection = $this->_computersAndGroups->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            // Lock all given computers and create a list sorted by LastContactDate.
            foreach ($computers as $id) {
                $computer = clone $this->_computer;
                $computer->fetchById($id);
                if (!$computer->lock()) {
                    throw new \RuntimeException("Cannot lock computer $id");
                }
                $timestamp = $computer['LastContactDate']->get(\Zend_Date::TIMESTAMP);
                $list[$timestamp] = $computer;
            }
            ksort($list);
            // Now that the list is sorted, renumber the indices
            $computers = array_values($list);

            // Newest computer will be the only one not to be deleted, remove it from the list
            $newest = array_pop($computers);

            if ($mergeCustomFields) {
                // Overwrite custom fields with values from oldest computer
                $newest->setUserDefinedInfo($computers[0]->getUserDefinedInfo()->getProperties());
            }

            if ($mergeGroups) {
                // Build list with all manual group assignments from old computers.
                // If more than 1 old computer is to be merged and the computers
                // have different assignments for the same group, the result is
                // undefined.
                $groupList = array();
                foreach ($computers as $computer) {
                    $groups = $computer->getGroupMemberships(\Model_GroupMembership::TYPE_MANUAL, null);
                    while ($group = $groups->fetchObject('Model_GroupMembership')) {
                        $groupList[$group['GroupId']] = $group['Membership'];
                    }
                }
                $newest->setGroups($groupList);
            }

            if ($mergePackages) {
                // Update the computer IDs directly. Assignments from all older
                // computers are merged. Exclude packages that are already assigned.
                $subQuery = 'ivalue NOT IN(SELECT ivalue FROM devices WHERE hardware_id = ? AND name = \'DOWNLOAD\')';
                foreach ($computers as $computer) {
                    $this->_itemConfig->update(
                        array('hardware_id' => $newest['Id']),
                        array(
                            'hardware_id' => $computer['Id'],
                            'name' => 'DOWNLOAD',
                            $subQuery => $newest['Id'],
                        )
                    );
                }
            }

            // Delete all older computers
            foreach ($computers as $computer) {
                $computer->delete(true, false);
            }
            // Unlock remaining computer
            $newest->unlock();
        } catch (\Exception $exception) {
            $connection->rollback();
            throw ($exception);
        }
        $connection->commit();
    }

    /**
     * Exclude a MAC address, serial or asset tag from being used as criteria
     * for duplicates search.
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @param string $value Value to be excluded
     * @throws \InvalidArgumentException if $criteria is invalid
     */
    public function allow($criteria, $value)
    {
        switch ($criteria) {
            case 'MacAddress':
                $table = $this->_duplicateMacaddresses;
                $column = 'macaddress';
                break;
            case 'Serial':
                $table = $this->_duplicateSerials;
                $column = 'serial';
                break;
            case 'AssetTag':
                $table = $this->_duplicateAssetTags;
                $column = 'assettag';
                break;
            default:
                throw new \InvalidArgumentException(
                    'Invalid criteria : ' . $criteria
                );
        }
        // Check for existing record to avoid constraint violation
        $data = array($column => $value);
        if ($table->select($data)->count() == 0) {
            $table->insert($data);
        }
    }
}
