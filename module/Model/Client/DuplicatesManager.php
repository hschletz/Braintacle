<?php

/**
 * Class for managing duplicate clients
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

namespace Model\Client;

use Braintacle\Direction;
use Braintacle\Duplicates\Criterion;
use Braintacle\Duplicates\DuplicatesColumn;
use Database\Table;
use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\Db\Sql\Select;

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
     * Client manager
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    public function __construct(
        Table\Clients $clients,
        Table\NetworkInterfaces $networkInterfaces,
        Table\DuplicateAssetTags $duplicateAssetTags,
        Table\DuplicateSerials $duplicateSerials,
        Table\DuplicateMacAddresses $duplicateMacAddresses,
        \Model\Client\ClientManager $clientManager,
    ) {
        $this->_clients = $clients;
        $this->_networkInterfaces = $networkInterfaces;
        $this->_duplicateAssetTags = $duplicateAssetTags;
        $this->_duplicateSerials = $duplicateSerials;
        $this->_duplicateMacaddresses = $duplicateMacAddresses;
        $this->_clientManager = $clientManager;
    }

    /**
     * Get query for duplicate values of given criterion.
     */
    protected function getDuplicateValues(Criterion $criterion): Select
    {
        switch ($criterion) {
            case Criterion::Name:
                $table = $this->_clients;
                $column = 'name';
                $count = 'name';
                break;
            case Criterion::AssetTag:
                $table = $this->_clients;
                $column = 'assettag';
                $count = 'assettag';
                $where = 'assettag NOT IN(SELECT assettag FROM braintacle_blacklist_assettags)';
                break;
            case Criterion::Serial:
                $table = $this->_clients;
                $column = 'ssn';
                $count = 'ssn';
                $where = 'ssn NOT IN(SELECT serial FROM blacklist_serials)';
                break;
            case Criterion::MacAddress:
                $table = $this->_networkInterfaces;
                $column = 'macaddr';
                $count = 'DISTINCT hardware_id'; // Count MAC addresses only once per client
                $where = 'macaddr NOT IN(SELECT macaddress FROM blacklist_macaddresses)';
                break;
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
     * Get number of duplicates for given criterion.
     */
    public function count(Criterion $criterion): int
    {
        $subQuery = $this->getDuplicateValues($criterion);
        $column = $subQuery->getRawState($subQuery::COLUMNS)[0];
        if ($criterion == Criterion::MacAddress) {
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
     * @return AbstractResultSet|iterable<Client>
     */
    public function find(Criterion $criterion, DuplicatesColumn $order, Direction $direction = Direction::Ascending)
    {
        $subQuery = $this->getDuplicateValues($criterion);
        $column = $subQuery->getRawState($subQuery::COLUMNS)[0];

        $select = $this->_clientManager->getClients(
            array('Id', 'Name', 'LastContactDate', 'Serial', 'AssetTag'),
            $order == DuplicatesColumn::MacAddress ? 'NetworkInterface.MacAddress' : $order->name,
            $direction->value,
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
        if ($order != DuplicatesColumn::Name) {
            // Secondary ordering by name
            $select->order('name');
        }
        if ($order != DuplicatesColumn::Id) {
            // Additional ordering by ID, to ensure multiple rows for the same
            // client are kept together where primary ordering allows
            $select->order('clients.id');
        }

        return $this->_clients->selectWith($select);
    }

    /**
     * Exclude a MAC address, serial or asset tag from being used as criteria
     * for duplicates search.
     */
    public function allow(Criterion $criterion, string $value)
    {
        /** @psalm-suppress UnhandledMatchCondition (Criterion::Name is not valid here) */
        $table = match ($criterion) {
            Criterion::MacAddress => $this->_duplicateMacaddresses,
            Criterion::Serial => $this->_duplicateSerials,
            Criterion::AssetTag => $this->_duplicateAssetTags,
        };
        /** @psalm-suppress UnhandledMatchCondition (Criterion::Name is not valid here) */
        $column = match ($criterion) {
            Criterion::MacAddress => 'macaddress',
            Criterion::Serial => 'serial',
            Criterion::AssetTag => 'assettag',
        };

        // Check for existing record to avoid constraint violation
        $data = array($column => $value);
        if ($table->select($data)->count() == 0) {
            $table->insert($data);
        }
    }
}
