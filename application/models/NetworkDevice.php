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
     * @param string $order Logical property to sort by. Default: null
     * @param string $direction One of [asc|desc].
     * @return Zend_Db_Statement Query result
     */
    public function fetch($filters, $order=null, $direction='asc')
    {
        $db = Model_Database::getAdapter();

        $select = $db->select()
            ->from('netmap', array('ip', 'mac', 'name', 'date'))
            ->where('mac NOT IN(SELECT macaddr FROM networks)');

        if (isset($filters['Type'])) {
            $filters['Identified'] = true;
        }
        foreach ($filters as $filter => $arg) {
            switch ($filter) {
                case 'Subnet':
                    $select->where('netid = ?', $arg);
                    break;
                case 'Mask':
                    $select->where('mask = ?', $arg);
                    break;
                case 'Type':
                    $select->where('type = ?', $arg);
                    break;
                case 'Identified':
                    if ($arg) {
                        $select->join(
                            'network_devices',
                            'netmap.mac = network_devices.macaddr',
                            array('description', 'type', 'user')
                        );
                    } else {
                        $select->where('mac NOT IN(SELECT macaddr FROM network_devices)');
                    }
                    break;
            }
        }

        $select->order(self::getOrder($order, $direction, $this->_propertyMap));

        return $this->_fetchAll($select->query());
    }

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
     * Instantiate a new object with data for the given MAC address
     * @param string|\Library\MacAddress $macaddress MAC address for which to retrieve information
     * @return Model_NetworkDevice|false
     */
    public function fetchByMacAddress($macaddress)
    {
        // Canonicalize the MAC address
        if (!($macaddress instanceof \Library\MacAddress)) {
            $macaddress = new \Library\MacAddress($macaddress);
        }
        $db = Model_Database::getAdapter();
        return $db->select()
            ->from('netmap', array('ip', 'mac', 'name', 'date'))
            ->joinLeft(
                'network_devices',
                'network_devices.macaddr=netmap.mac',
                array('description', 'type', 'user')
            )
            ->where('netmap.mac=?', $macaddress)
            ->query()
            ->fetchObject('Model_NetworkDevice');
    }


    /**
     * Get all userdefined categories for identified devices
     * @return array
     */
    static function getCategories()
    {
        $db = Model_Database::getAdapter();
        return $db->fetchCol('SELECT name FROM devicetype ORDER BY name');
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


    /**
     * Delete this device from the database, including manual identification data (if present)
     */
    public function delete()
    {
        $db = Model_Database::getAdapter();
        $db->delete('network_devices', array('macaddr=?' => $this->getMacAddress()));
        $db->delete('netmap', array('mac=?' => $this->getMacAddress()));
    }

}
