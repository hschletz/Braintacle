<?php

/**
 * Class for managing duplicate clients
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

namespace Model\Client;

use Database\Table;
use Model\SoftwareManager;
use RuntimeException;

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
     * Option for merge(): Merge client config
     */
    const MERGE_CONFIG = 'mergeConfig';

    /**
     * Option for merge(): Preserve custom fields from oldest client
     */
    const MERGE_CUSTOM_FIELDS = 'mergeCustomFields';

    /**
     * Option for merge(): Preserve manual group assignments from old clients
     */
    const MERGE_GROUPS = 'mergeGroups';

    /**
     * Option for merge(): Preserve package assignments from old clients missing on new client
     */
    const MERGE_PACKAGES = 'mergePackages';

    /**
     * Option for merge(): Preserve manually entered Windows product key
     */
    const MERGE_PRODUCT_KEY = 'mergeProductKey';

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
     * Client manager
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    /**
     * Software manager
     */
    private SoftwareManager $_softwareManager;

    /**
     * Constructor
     *
     * @param \Database\Table\Clients $clients
     * @param \Database\Table\NetworkInterfaces $networkInterfaces
     * @param \Database\Table\DuplicateAssetTags $duplicateAssetTags
     * @param \Database\Table\DuplicateSerials $duplicateSerials
     * @param \Database\Table\DuplicateMacAddresses $duplicateMacAddresses
     * @param \Database\Table\ClientConfig $clientConfig
     * @param \Model\Client\ClientManager $clientManager
     * @param \Model\SoftwareManager $softwareManager
     */
    public function __construct(
        Table\Clients $clients,
        Table\NetworkInterfaces $networkInterfaces,
        Table\DuplicateAssetTags $duplicateAssetTags,
        Table\DuplicateSerials $duplicateSerials,
        Table\DuplicateMacAddresses $duplicateMacAddresses,
        Table\ClientConfig $clientConfig,
        \Model\Client\ClientManager $clientManager,
        \Model\SoftwareManager $softwareManager
    ) {
        $this->_clients = $clients;
        $this->_networkInterfaces = $networkInterfaces;
        $this->_duplicateAssetTags = $duplicateAssetTags;
        $this->_duplicateSerials = $duplicateSerials;
        $this->_duplicateMacaddresses = $duplicateMacAddresses;
        $this->_clientConfig = $clientConfig;
        $this->_clientManager = $clientManager;
        $this->_softwareManager = $softwareManager;
    }

    /**
     * Get query for duplicate values of given griteria
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @return \Laminas\Db\Sql\Select
     * @throws \InvalidArgumentException if $criteria is invalid
     */
    protected function getDuplicateValues($criteria)
    {
        switch ($criteria) {
            case 'Name':
                $table = $this->_clients;
                $column = 'name';
                $count = 'name';
                break;
            case 'AssetTag':
                $table = $this->_clients;
                $column = 'assettag';
                $count = 'assettag';
                $where = 'assettag NOT IN(SELECT assettag FROM braintacle_blacklist_assettags)';
                break;
            case 'Serial':
                $table = $this->_clients;
                $column = 'ssn';
                $count = 'ssn';
                $where = 'ssn NOT IN(SELECT serial FROM blacklist_serials)';
                break;
            case 'MacAddress':
                $table = $this->_networkInterfaces;
                $column = 'macaddr';
                $count = 'DISTINCT hardware_id'; // Count MAC addresses only once per client
                $where = 'macaddr NOT IN(SELECT macaddress FROM blacklist_macaddresses)';
                break;
            default:
                throw new \InvalidArgumentException('Invalid criteria: ' . $criteria);
        }
        $select = $table->getSql()->select();
        $select->columns(array($column))
               ->group($column)
               ->having("COUNT($count) > 1");
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
        $subQuery = $this->getDuplicateValues($criteria);
        $column = $subQuery->getRawState($subQuery::COLUMNS)[0];
        if ($criteria == 'MacAddress') {
            $table = $this->_networkInterfaces;
            $count = 'DISTINCT hardware_id'; // Count clients only once
        } else {
            $table = $this->_clients;
            $count = $column;
        }

        $sql = $table->getSql();
        $select = $sql->select();
        $select->columns(
            array(
                'num_clients' => new \Laminas\Db\Sql\Literal("COUNT($count)")
            )
        );
        $select->where(array(new \Laminas\Db\Sql\Predicate\In($column, $subQuery)));
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        return $row['num_clients'];
    }

    /**
     * Retrieve duplicate clients with given criteria
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @param string $order Sorting order (default: 'Id')
     * @param string $direction One of asc|desc (default: 'asc')
     * @return \Laminas\Db\ResultSet\AbstractResultSet \Model\Client\Client iterator
     */
    public function find($criteria, $order = 'Id', $direction = 'asc')
    {
        $subQuery = $this->getDuplicateValues($criteria);
        $column = $subQuery->getRawState($subQuery::COLUMNS)[0];

        $select = $this->_clientManager->getClients(
            array('Id', 'Name', 'LastContactDate', 'Serial', 'AssetTag'),
            $order,
            $direction,
            null,
            null,
            null,
            null,
            false,
            false,
            false
        );
        $select->quantifier(\Laminas\Db\Sql\Select::QUANTIFIER_DISTINCT);
        $select->join(
            'networks',
            'networks.hardware_id = clients.id',
            array('networkinterface_macaddr' => 'macaddr'),
            $select::JOIN_LEFT
        )
        ->where(array(new \Laminas\Db\Sql\Predicate\In($column, $subQuery)));
        if ($order != 'Name') {
            // Secondary ordering by name
            $select->order('name');
        }
        if ($order != 'Id') {
            // Additional ordering by ID, to ensure multiple rows for the same
            // client are kept together where primary ordering allows
            $select->order('clients.id');
        }

        return $this->_clients->selectWith($select);
    }

    /**
     * Merge clients
     *
     * This method is used to eliminate duplicates in the database. Based on the
     * last contact, the newest entry is preserved. All older entries are
     * deleted. Some information from the older entries can be preserved on the
     * remaining client.
     *
     * @param integer[] $clientIds IDs of clients to merge
     * @param array $options Attributes to merge, see MERGE_* constants
     * @throws \RuntimeException if an affected client cannot be locked
     */
    public function merge(array $clientIds, array $options)
    {
        // Remove duplicate IDs
        $clientIds = array_unique($clientIds);
        if (count($clientIds) < 2) {
            return; // Nothing to do
        }

        $connection = $this->_clients->getConnection();
        $connection->beginTransaction();
        try {
            // Lock all given clients and create a list sorted by LastContactDate.
            $clients = [];
            foreach ($clientIds as $id) {
                $client = $this->_clientManager->getClient($id);
                if (!$client->lock()) {
                    throw new \RuntimeException("Cannot lock client $id");
                }
                $timestamp = $client['LastContactDate']->getTimestamp();
                if (isset($clients[$timestamp])) {
                    throw new RuntimeException('Cannot merge because clients have identical lastContactDate');
                }
                $clients[$timestamp] = $client;
            }
            ksort($clients);
            // Now that the list is sorted, renumber the indices
            $clients = array_values($clients);

            // Newest client will be the only one not to be deleted, remove it from the list
            $newest = array_pop($clients);

            if (in_array(self::MERGE_CONFIG, $options)) {
                $this->mergeConfig($newest, $clients);
            }
            if (in_array(self::MERGE_CUSTOM_FIELDS, $options)) {
                $this->mergeCustomFields($newest, $clients);
            }
            if (in_array(self::MERGE_GROUPS, $options)) {
                $this->mergeGroups($newest, $clients);
            }
            if (in_array(self::MERGE_PACKAGES, $options)) {
                $this->mergePackages($newest, $clients);
            }
            if (in_array(self::MERGE_PRODUCT_KEY, $options)) {
                $this->mergeProductKey($newest, $clients);
            }

            // Delete all older clients
            foreach ($clients as $client) {
                $this->_clientManager->deleteClient($client, false);
            }
            // Unlock remaining client
            $newest->unlock();
            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollback();
            throw ($exception);
        }
    }

    /**
     * Overwrite custom fields on newest client with values from oldest client
     *
     * @param \Model\Client\Client $newestClient
     * @param \Model\Client\Client[] $olderClients sorted by LastContactDate (ascending)
     */
    public function mergeCustomFields($newestClient, $olderClients)
    {
        $newestClient->setCustomFields($olderClients[0]['CustomFields']);
    }

    /**
     * Merge manual group memberships from older clients into newest client
     *
     * If clients have different membership types for the same group, the
     * resulting membership type is undefined.
     *
     * @param \Model\Client\Client $newestClient
     * @param \Model\Client\Client[] $olderClients sorted by LastContactDate (ascending)
     */
    public function mergeGroups($newestClient, $olderClients)
    {
        $groupList = [];
        foreach ($olderClients as $client) {
            $groupList += $client->getGroupMemberships(\Model\Client\Client::MEMBERSHIP_MANUAL);
        }
        $newestClient->setGroupMemberships($groupList);
    }

    /**
     * Add missing package assignments from older clients on the newest client
     *
     * @param \Model\Client\Client $newestClient
     * @param \Model\Client\Client[] $olderClients sorted by LastContactDate (ascending)
     */
    public function mergePackages($newestClient, $olderClients)
    {
        $id = $newestClient['Id'];

        // Exclude packages that are already assigned.
        $notIn = $this->_clientConfig->getSql()->select();
        $notIn->columns(['ivalue'])->where(['hardware_id' => $id, 'name' => 'DOWNLOAD']);

        foreach ($olderClients as $client) {
            $where = [
                'hardware_id' => $client['Id'],
                new \Laminas\Db\Sql\Predicate\Operator('name', '!=', 'DOWNLOAD_SWITCH'),
                new \Laminas\Db\Sql\Predicate\Like('name', 'DOWNLOAD%'),
            ];
            // Construct list of package IDs because MySQL does not support subquery here
            $exclude = array_column($this->_clientConfig->selectWith($notIn)->toArray(), 'ivalue');
            // Avoid empty list
            if ($exclude) {
                $where[] = new \Laminas\Db\Sql\Predicate\NotIn('ivalue', $exclude);
            }
            // Update the client IDs directly.
            $this->_clientConfig->update(array('hardware_id' => $id), $where);
        }
    }

    /**
     * Set newest client's Windows manual product key to the newest key of all given clients
     *
     * @param \Model\Client\Client $newestClient
     * @param \Model\Client\Client[] $olderClients sorted by LastContactDate (ascending)
     */
    public function mergeProductKey($newestClient, $olderClients)
    {
        if (!$newestClient['Windows']) {
            return;
        }
        if ($newestClient['Windows']['ManualProductKey']) {
            return;
        }
        // Iterate over all clients, newest first, and pick first key found.
        foreach (array_reverse($olderClients) as $client) {
            $windows = $client['Windows'];
            if ($windows) {
                if ($windows['ManualProductKey']) {
                    $this->_softwareManager->setProductKey($newestClient, $windows['ManualProductKey']);
                    return;
                }
            }
        }
    }

    /**
     * Merge config on newest client with values from older clients
     *
     * If a config option is not set on the newest client, set it to a
     * value configured on an older client (if any). If multiple older clients
     * have a value configured, the value from the most recent client is used.
     *
     * @param \Model\Client\Client $newestClient
     * @param \Model\Client\Client[] $olderClients sorted by LastContactDate (ascending)
     */
    public function mergeConfig($newestClient, $olderClients)
    {
        $options = [];
        foreach (array_reverse($olderClients) as $client) {
            // Add options that are not present yet
            $options += $client->getExplicitConfig();
        }
        // Remove options that are present on the newest client
        $options = array_diff_key($options, $newestClient->getExplicitConfig());

        foreach ($options as $option => $value) {
            $newestClient->setConfig($option, $value);
        }
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
