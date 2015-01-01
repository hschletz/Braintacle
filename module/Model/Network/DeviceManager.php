<?php
/**
 * Network device manager
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

namespace Model\Network;

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
    )
    {
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
     * The 'Description', 'Type' and 'IdentifiedBy' properties are only set if
     * the 'Identified' filter is TRUE.
     *
     * @param array $filters Filters to apply
     * @param string $order Property to sort by. Default: null
     * @param string $direction One of [asc|desc].
     * @return \Zend\Db\ResultSet\AbstractResultSet Result set producing \Model_NetworkDevice
     */
    public function getDevices($filters, $order=null, $direction='asc')
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
                            array('description', 'type', 'user')
                        );
                    } else {
                        $select->where('mac NOT IN(SELECT macaddr FROM network_devices)');
                    }
                    break;
            }
        }

        $networkDevice = new \Model_NetworkDevice;
        $select->order(\Model_NetworkDevice::getOrder($order, $direction, $networkDevice->getPropertyMap()));

        return $this->_networkDevicesScanned->selectWith($select);
    }

    /**
     * Get device with given MAC address
     *
     * @param string|\Library\MacAddress $macAddress MAC address
     * @return \Model_NetworkDevice
     * @throws \Model\Network\RuntimeException if no scanned device with the given MAC address exists
     */
    public function getDevice($macAddress)
    {
        // Canonicalize the MAC address
        if (!($macAddress instanceof \Library\MacAddress)) {
            $macAddress = new \Library\MacAddress($macAddress);
        }
        $select = $this->_networkDevicesScanned->getSql()->select();
        $select->columns(array('ip', 'mac', 'name', 'date'))
               ->join(
                   'network_devices',
                   'macaddr = mac',
                   array('description', 'type', 'user'),
                   \Zend\Db\Sql\Select::JOIN_LEFT
               )
               ->where(array('mac' => $macAddress));
        $device = $this->_networkDevicesScanned->selectWith($select)->current();
        if (!$device) {
            throw new RuntimeException('Unknown MAC address: ' . $macAddress);
        }
        return $device;
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
        $db = \Model_Database::getAdapter();
        $db->delete('network_devices', array('macaddr=?' => $macAddress));
        $db->delete('netmap', array('mac=?' => $macAddress));
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
        // become part of an inventoried computer.
        $select = $this->_networkDeviceTypes->getSql()->select();
        $select->columns(
            array(
                'name',
                'num_devices' => new \Zend\Db\Sql\Literal('COUNT(type)')
            )
        )->join(
            'network_devices',
            new \Zend\Db\Sql\Literal('type = name AND macaddr NOT IN(SELECT macaddr FROM networks)'),
            array(),
            \Zend\Db\Sql\Select::JOIN_LEFT
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
        if ($this->_networkDeviceTypes->update(array('name' => $new), array('name' => $old)) != 1) {
            $connection->rollback();
            throw new \RuntimeException('Network device type does not exist: ' . $old);
        }
        $this->_networkDevicesIdentified->update(array('type' => $new), array('type' => $old));
        $connection->commit();
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

        $this->_networkDevicesIdentified->delete(
            array(
                'type' => $description,
                'macaddr IN(SELECT macaddr FROM networks)'
            )
        );
        if ($this->_networkDevicesIdentified->select(array('type' => $description))->count()) {
            $connection->rollback();
            throw new \RuntimeException('Network device type still in use: ' . $description);
        }
        if ($this->_networkDeviceTypes->delete(array('name' => $description)) != 1) {
            $connection->rollback();
            throw new \RuntimeException('Network device type does not exist: ' . $description);
        }

        $connection->commit();
    }
}
