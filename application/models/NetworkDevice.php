<?php
/**
 * Class representing a network device
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
 *
 * @package Models
 */
/** A network device (router, printer, uninventoried computer and similar)
 *
 *
 * The following properties are available:
 *
 * - <b>IpAddress:</b> IP address
 * - <b>MacAddress:</b> MAC address
 * - <b>Hostname:</b> Hostname
 * - <b>DiscoveryDate:</b> Timestamp of IP discovery
 * - <b>Description:</b> Description (only identified devices)
 * - <b>Type:</b> Type (only identified devices)
 * - <b>IdentifiedBy:</b> User who identified the device
 * - <b>Vendor:</b> Vendor, derived from MAC address
 * @package Models
 */
class Model_NetworkDevice extends Model_Abstract
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // values from 'netmap' table
        'IpAddress' => 'ip',
        'MacAddress' => 'mac',
        'Hostname' => 'name',
        'DiscoveryDate' => 'date',
        // values from 'network_devices' table
        'Description' => 'description',
        'Type' => 'type',
        'IdentifiedBy' => 'user',
        // calculated values
        'Vendor' => '',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'DiscoveryDate' => 'timestamp',
    );

    /**
     * Retrieve a property by its logical name
     *
     * Converts MacAddress into a \Library\MacAddress object and supports Vendor
     * property.
     */
    function getProperty($property, $rawValue=false)
    {
        if ($property == 'Vendor') {
            return $this->getMacAddress()->getVendor();
        }

        $value = parent::getProperty($property, $rawValue);

        if ($rawValue or $property != 'MacAddress') {
            return $value;
        }

        return new \Library\MacAddress($value);
    }

    /**
     * Compose ORDER BY clause from logical identifier
     *
     * Maps Vendor property to MacAddress since this information is not
     * contained within the database.
     */
    static function getOrder($order, $direction, $propertyMap)
    {
        if ($order == 'Vendor') {
            $order = 'MacAddress';
        }
        return parent::getOrder($order, $direction, $propertyMap);
    }


    /**
     * Populate object with data from an array
     * @param array $data Associative array (property => value).
     */
    public function fromArray($data)
    {
        foreach ($data as $property => $value) {
            $this->setProperty($property, $value);
        }
    }


    /**
     * Store identification data (type, description, user) in database
     */
    public function save()
    {
        $mac = $this->getMacAddress();
        if ($mac == '') {
            throw new UnexpectedValueException('Uninitialized NetworkDevice object');
        }

        $auth = \Library\Application::getService('Library\AuthenticationService');
        $this->setIdentifiedBy($auth->getIdentity());

        $db = Model_Database::getAdapter();
        $data = array(
            'description' => $this->getDescription(),
            'type' => $this->getType(),
            $db->quoteIdentifier('user') => $this->getIdentifiedBy(), // Quoting required for PostgreSQL
        );
        if ($db->fetchOne('SELECT macaddr FROM network_devices WHERE macaddr=?', $mac)) {
            $db->update('network_devices', $data, array('macaddr=?' => $mac));
        } else {
            $data['macaddr'] = $mac;
            $db->insert('network_devices', $data);
        }
    }
}
