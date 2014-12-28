<?php
/**
 * Network device manager
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
     * NetworkDevicesIdentified table
     * @var \Database\Table\NetworkDevicesIdentified
     */
    protected $_networkDevicesIdentified;

    /**
     * Constructor
     *
     * @param \Database\Table\NetworkDeviceTypes $networkDeviceTypes
     * @param \Database\Table\NetworkDevicesIdentified $networkDevicesIdentified
     */
    public function __construct(
        \Database\Table\NetworkDeviceTypes $networkDeviceTypes,
        \Database\Table\NetworkDevicesIdentified $networkDevicesIdentified
    )
    {
        $this->_networkDeviceTypes = $networkDeviceTypes;
        $this->_networkDevicesIdentified = $networkDevicesIdentified;
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
