<?php
/**
 * Class for managing duplicate clients
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
 */

namespace Model\Client;

use Database\Table;

/**
 * Class for managing duplicate clients
 *
 * Duplicates are clients with more than one entry in the database. These occur
 * when the client's OS is reinstalled and previous agent configuration is lost
 * so that a new client ID is generated. Agent bugs and misconfiguration can
 * cause duplicates, too.
 *
 * This class provides methods to identify duplicates based on various criteria
 * which are supposed to be unique:
 *
 * - Name. It is possible to have multiple clients with the same name, but this
 *   is bad and it is recommended to rename affected clients.
 * - MAC address. These are not strictly bound to clients, but to network
 *   interfaces, and can sometimes move to a different client. Some virtual
 *   interfaces (like PPP or VPN adapters) may also have non-unique MAC
 *   addresses. For this reason, particular MAC addresses can be blacklisted via
 *   allow() to prevent them from being used as duplicate criteria.
 * - Serial. Usually an immutable hardware property. Some products may have no
 *   or non-unique serials which can be blacklisted via allow().
 * - Asset tag. Usually hardcoded or user provided in BIOS/UEFI configuration.
 *   May be non-unique and therefore get blacklisted via allow().
 *
 * Once identified, duplicates can be eliminated via merge().
 */
class DuplicatesManager
{
    /**
     * Clients prototype
     * @var \Database\Table\Clients
     */
    protected $_clients;

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
     * ClientConfig prototype
     * @var \Database\Table\ClientConfig
     */
    protected $_clientConfig;

    /**
     * Computer prototype
     * @var \Model_Computer
     */
    protected $_computer;

    /**
     * Constructor
     *
     * @param \Database\Table\Clients $clients
     * @param \Database\Table\NetworkInterfaces $networkInterfaces
     * @param \Database\Table\DuplicateAssetTags $duplicateAssetTags
     * @param \Database\Table\DuplicateSerials $duplicateSerials
     * @param \Database\Table\DuplicateMacAddresses $duplicateMacAddresses
     * @param \Database\Table\ClientConfig $clientConfig
     * @param \Model_Computer $computer
     */
    public function __construct(
        Table\Clients $clients,
        Table\NetworkInterfaces $networkInterfaces,
        Table\DuplicateAssetTags $duplicateAssetTags,
        Table\DuplicateSerials $duplicateSerials,
        Table\DuplicateMacAddresses $duplicateMacAddresses,
        Table\ClientConfig $clientConfig,
        \Model_Computer $computer
    )
    {
        $this->_clients = $clients;
        $this->_networkInterfaces = $networkInterfaces;
        $this->_duplicateAssetTags = $duplicateAssetTags;
        $this->_duplicateSerials = $duplicateSerials;
        $this->_duplicateMacaddresses = $duplicateMacAddresses;
        $this->_clientConfig = $clientConfig;
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
                $table = $this->_clients;
                $column = 'name';
                break;
            case 'AssetTag':
                $table = $this->_clients;
                $column = 'assettag';
                $where = 'assettag NOT IN(SELECT assettag FROM braintacle_blacklist_assettags)';
                break;
            case 'Serial':
                $table = $this->_clients;
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
               ->group($column)
               ->having("COUNT($column) > 1");
        if (isset($where)) {
            $select->where($where);
        }
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
        if ($criteria == 'MacAddress') {
            $table = $this->_networkInterfaces;
        } else {
            $table = $this->_clients;
        }
        $subQuery = $subQuery->getSqlString($this->_clients->getAdapter()->getPlatform());

        $sql = $table->getSql();
        $select = $sql->select();
        $select->columns(
            array(
                'num_clients' => new \Zend\Db\Sql\Expression("COUNT($column)")
            )
        );
        $select->where("$column IN($subQuery)");
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return $row['num_clients'];
    }

    /**
     * Retrieve duplicate clients with given criteria
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @param string $order Sorting order (default: 'Id')
     * @param string $direction One of asc|desc (default: 'asc')
     * @return \Zend\Db\ResultSet\AbstractResultSet \Model\Client\Client iterator
     */
    public function find($criteria, $order='Id', $direction='asc')
    {
        $subQuery = $this->_getDuplicateValues($criteria);
        $columns = $subQuery->getRawState($subQuery::COLUMNS);
        $column = $columns[0];
        $subQuery = $subQuery->getSqlString($this->_clients->getAdapter()->getPlatform());

        $sql = $this->_clients->getSql();
        $select = $sql->select();
        $select->columns(array('id', 'name', 'lastcome', 'ssn', 'assettag'));
        $select->join(
            'networks',
            'networks.hardware_id = clients.id',
            array('networkinterface_macaddr' => 'macaddr'),
            $select::JOIN_LEFT
        )
        ->where("$column IN($subQuery)");
        $select->order(\Model_Computer::getOrder($order, $direction, $this->_computer->getPropertyMap()));
        if ($order != 'Name') {
            // Secondary ordering by name
            $select->order('name');
        }
        if ($order != 'Id') {
            // Additional ordering by ID, to ensure multiple rows for the same
            // client are kept together where primary ordering allows
            $select->order('clients.id');
        }

        $resultSet = new \Zend\Db\ResultSet\HydratingResultSet(
            new \Zend\Stdlib\Hydrator\ArraySerializable,
            $this->_computer
        );
        $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());
        return $resultSet;
    }

    /**
     * Merge clients
     *
     * This method is used to eliminate duplicates in the database. Based on the
     * last contact, the newest entry is preserved. All older entries are
     * deleted. Some information from the older entries can be preserved on the
     * remaining client.
     *
     * @param integer[] $clients IDs of clients to merge
     * @param bool $mergeCustomFields Preserve custom fields from oldest client
     * @param bool $mergeGroups Preserve manual group assignments from old clients
     * @param bool $mergePackages Preserve package assignments from old clients missing on new client
     * @throws \RuntimeException if an affected client cannot be locked
     */
    public function merge(array $clients, $mergeCustomFields, $mergeGroups, $mergePackages)
    {
        // Remove duplicate IDs
        $clients = array_unique($clients);
        if (count($clients) < 2) {
            return; // Nothing to do
        }

        $connection = $this->_clients->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            // Lock all given clients and create a list sorted by LastContactDate.
            foreach ($clients as $id) {
                $client = clone $this->_computer;
                $client->fetchById($id);
                if (!$client->lock()) {
                    throw new \RuntimeException("Cannot lock client $id");
                }
                $timestamp = $client['LastContactDate']->get(\Zend_Date::TIMESTAMP);
                $list[$timestamp] = $client;
            }
            ksort($list);
            // Now that the list is sorted, renumber the indices
            $clients = array_values($list);

            // Newest client will be the only one not to be deleted, remove it from the list
            $newest = array_pop($clients);

            if ($mergeCustomFields) {
                // Overwrite custom fields with values from oldest client
                $newest->setUserDefinedInfo($clients[0]->getUserDefinedInfo());
            }

            if ($mergeGroups) {
                // Build list with all manual group assignments from old clients.
                // If more than 1 old client is to be merged and the clients
                // have different assignments for the same group, the result is
                // undefined.
                $groupList = array();
                foreach ($clients as $client) {
                    foreach ($client->getGroups(\Model_GroupMembership::TYPE_MANUAL, null) as $group) {
                        $groupList[$group['GroupId']] = $group['Membership'];
                    }
                }
                $newest->setGroups($groupList);
            }

            if ($mergePackages) {
                // Update the client IDs directly. Assignments from all older
                // clients are merged. Exclude packages that are already assigned.
                $subQuery = 'ivalue NOT IN(SELECT ivalue FROM devices WHERE hardware_id = ? AND name = \'DOWNLOAD\')';
                foreach ($clients as $client) {
                    $this->_clientConfig->update(
                        array('hardware_id' => $newest['Id']),
                        array(
                            'hardware_id' => $client['Id'],
                            "name != 'DOWNLOAD_SWITCH'",
                            "name LIKE 'DOWNLOAD%'",
                            $subQuery => $newest['Id'],
                        )
                    );
                }
            }

            // Delete all older clients
            foreach ($clients as $client) {
                $client->delete(true, false);
            }
            // Unlock remaining client
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
