<?php

/**
 * Network device manager
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

namespace Model\Network;

use Laminas\Db\Sql\Predicate\Expression;

/**
 * Network device manager
 */
class DeviceManager
{
    /**
     * NetworkDeviceTypes table
     * @var \Database\Table\NetworkDeviceTypes
     */
    protected $_networkDeviceTypes;

    /**
     * NetworkDevicesScanned table
     * @var \Database\Table\NetworkDevicesScanned
     */
    protected $_networkDevicesScanned;

    /**
     * NetworkDevicesIdentified table
     * @var \Database\Table\NetworkDevicesIdentified
     */
    protected $_networkDevicesIdentified;

    /**
     * Constructor
     *
     * @param \Database\Table\NetworkDeviceTypes $networkDeviceTypes
     * @param \Database\Table\NetworkDevicesScanned $networkDevicesScanned
     * @param \Database\Table\NetworkDevicesIdentified $networkDevicesIdentified
     */
    public function __construct(
        \Database\Table\NetworkDeviceTypes $networkDeviceTypes,
        \Database\Table\NetworkDevicesScanned $networkDevicesScanned,
        \Database\Table\NetworkDevicesIdentified $networkDevicesIdentified
    ) {
        $this->_networkDeviceTypes = $networkDeviceTypes;
        $this->_networkDevicesScanned = $networkDevicesScanned;
        $this->_networkDevicesIdentified = $networkDevicesIdentified;
    }

    /**
     * Retrieve devices
     *
     * Available filters are:
     *
     * - **Subnet:** Network address, most useful in conjunction with 'Mask' filter
     * - **Mask:** Network mask, most useful in conjunction with 'Subnet' filter
     * - **Type:** Device type (description string), implies Identified=TRUE
     * - **Identified:** Boolean, selects only identified or unidentified devices
     *
     * The 'Description' and 'Type' properties are only set if the 'Identified'
     * filter is TRUE.
     *
     * @param array $filters Filters to apply
     * @param string $order Property to sort by. Default: null
     * @param string $direction One of [asc|desc].
     * @return \Laminas\Db\ResultSet\AbstractResultSet Result set producing \Model\Network\Device
     */
    public function getDevices($filters, $order = null, $direction = 'asc')
    {
        $select = $this->_networkDevicesScanned->getSql()->select();
        $select->columns(array('ip', 'mac', 'name', 'date'))
               ->where('mac NOT IN(SELECT macaddr FROM networks)');

        if (isset($filters['Type'])) {
            $filters['Identified'] = true;
        }
        foreach ($filters as $filter => $arg) {
            switch ($filter) {
                case 'Subnet':
                    $select->where(array('netid' => $arg));
                    break;
                case 'Mask':
                    $select->where(array('mask' => $arg));
                    break;
                case 'Type':
                    $select->where(array('type' => $arg));
                    break;
                case 'Identified':
                    if ($arg) {
                        $select->join(
                            'network_devices',
                            'mac = macaddr',
                            array('description', 'type')
                        );
                    } else {
                        $select->where('mac NOT IN(SELECT macaddr FROM network_devices)');
                    }
                    break;
            }
        }

        if ($order == 'Vendor') {
            // This is not stored in the database. The best guess is sorting by
            // MAC address which has a vendor-specific prefix.
            $order = 'MacAddress';
        }
        if ($order) {
            $order = $this->_networkDevicesScanned->getHydrator()->extractName($order);
        }
        if ($order) {
            $select->order(array($order => $direction));
        }

        return $this->_networkDevicesScanned->selectWith($select);
    }

    /**
     * Get device with given MAC address
     *
     * @param string|\Library\MacAddress $macAddress MAC address
     * @return \Model\Network\Device
     * @throws \Model\Network\RuntimeException if no scanned device with the given MAC address exists
     */
    public function getDevice($macAddress)
    {
        // Canonicalize the MAC address
        if (!($macAddress instanceof \Library\MacAddress)) {
            $macAddress = new \Library\MacAddress($macAddress);
        }
        $macAddress = $macAddress->getAddress();
        $select = $this->_networkDevicesScanned->getSql()->select();
        $select->columns(array('ip', 'mac', 'name', 'date'))
               ->join(
                   'network_devices',
                   'macaddr = mac',
                   array('description', 'type'),
                   \Laminas\Db\Sql\Select::JOIN_LEFT
               )
               ->where(array('mac' => $macAddress));
        $device = $this->_networkDevicesScanned->selectWith($select)->current();
        if (!$device) {
            throw new RuntimeException('Unknown MAC address: ' . $macAddress);
        }
        return $device;
    }

    /**
     * Store identification data for a device
     *
     * @param \Library\MacAddress $macAddress MAC address
     * @param string $type Device type
     * @param string $description Description
     */
    public function saveDevice(\Library\MacAddress $macAddress, $type, $description)
    {
        $macAddress = (string) $macAddress;
        $data = array(
            'type' => $type,
            'description' => $description,
        );
        if ($this->_networkDevicesIdentified->select(array('macaddr' => $macAddress))->count()) {
            $this->_networkDevicesIdentified->update($data, array('macaddr' => $macAddress));
        } else {
            $data['macaddr'] = $macAddress;
            $this->_networkDevicesIdentified->insert($data);
        }
    }

    /**
     * Delete a device from the database, including manual identification data
     *
     * @param string|\Library\MacAddress $macAddress MAC address
     */
    public function deleteDevice($macAddress)
    {
        // Canonicalize the MAC address
        if (!($macAddress instanceof \Library\MacAddress)) {
            $macAddress = new \Library\MacAddress($macAddress);
        }
        $macAddress = $macAddress->getAddress();
        $this->_networkDevicesIdentified->delete(array('macaddr' => $macAddress));
        $this->_networkDevicesScanned->delete(array('mac' => $macAddress));
    }

    /**
     * Get all defined types
     *
     * @return string[] Types ordered by name
     */
    public function getTypes()
    {
        $types = $this->_networkDeviceTypes->fetchCol('name');
        sort($types);
        return $types;
    }

    /**
     * Retrieve all defined device types and the number of devices
     *
     * @return array type => count pairs
     */
    public function getTypeCounts()
    {
        // The JOIN condition excludes stale entries where the interface has
        // become part of an inventoried client.
        $select = $this->_networkDeviceTypes->getSql()->select();
        $select->columns(
            array(
                'name',
                'num_devices' => new \Laminas\Db\Sql\Literal('COUNT(type)')
            )
        )->join(
            'network_devices',
            new Expression('type = name AND macaddr NOT IN(SELECT macaddr FROM networks)'),
            array(),
            \Laminas\Db\Sql\Select::JOIN_LEFT
        )
        ->group('name')
        ->order('name');

        $counts = array();
        foreach ($this->_networkDeviceTypes->selectWith($select) as $type) {
            $counts[$type['name']] = $type['num_devices'];
        }
        return $counts;
    }

    /**
     * Add a type definition
     *
     * @param string $description Description of new type
     * @throws \RuntimeException if a definition with the same description already exists.
     **/
    public function addType($description)
    {
        if ($this->_networkDeviceTypes->select(array('name' => $description))->count()) {
            throw new \RuntimeException('Network device type already exists: ' . $description);
        }
        $this->_networkDeviceTypes->insert(array('name' => $description));
    }

    /**
     * Rename a type definition
     *
     * @param string $old Old description of type
     * @param string $new New description of type
     * @throws \RuntimeException if $old does not exist or $new already exists
     **/
    public function renameType($old, $new)
    {
        if ($this->_networkDeviceTypes->select(array('name' => $new))->count()) {
            throw new \RuntimeException('Network device type already exists: ' . $new);
        }
        $connection = $this->_networkDeviceTypes->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            if ($this->_networkDeviceTypes->update(array('name' => $new), array('name' => $old)) != 1) {
                throw new \RuntimeException('Network device type does not exist: ' . $old);
            }
            $this->_networkDevicesIdentified->update(array('type' => $new), array('type' => $old));
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }

    /**
     * Delete a type definition
     *
     * @param string $description Description of new type
     * @throws \RuntimeException if the type does not exist or is still assigned to a device
     **/
    public function deleteType($description)
    {
        $connection = $this->_networkDeviceTypes->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $this->_networkDevicesIdentified->delete(
                array(
                    'type' => $description,
                    'macaddr IN(SELECT macaddr FROM networks)'
                )
            );
            if ($this->_networkDevicesIdentified->select(array('type' => $description))->count()) {
                throw new \RuntimeException('Network device type still in use: ' . $description);
            }
            if ($this->_networkDeviceTypes->delete(array('name' => $description)) != 1) {
                throw new \RuntimeException('Network device type does not exist: ' . $description);
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }
}
