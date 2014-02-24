<?php
/**
 * Class representing a subnet
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
/** A single subnet
 *
 * This class provides an interface to all subnets and general statistics about
 * details about inventoried, categorized and unknown interfaces.
 *
 * The following properties are available:
 *
 * - <b>Address:</b> Network Address, example: 192.168.1.0
 * - <b>Mask:</b> Subnet Mask, example: 255.255.255.0
 * - <b>AddressWithMask:</b> Short Address/Mask notation, example: 192.168.1.0/24
 * - <b>Name:</b> Assigned name (NULL if no name has been assigned)
 * - <b>NumInventoried:</b> Number of interfaces belonging to an inventoried computer
 * - <b>NumIdentified:</b> Number of uninventoried, but manually identified interfaces
 * - <b>NumUnknown:</b> Number of uninventoried and unidentified interfaces
 * @package Models
 */
class Model_Subnet extends Model_Abstract
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // values from 'networks' table
        'Address' => 'ipsubnet',
        'Mask' => 'ipmask',
        // values from 'subnet' table
        'Name' => 'name',
        // values from aggregates
        'NumInventoried' => 'num_inventoried',
        'NumIdentified' => 'num_identified',
        'NumUnknown' => 'num_unknown',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'NumInventoried' => 'integer',
        'NumIdentified' => 'integer',
        'NumUnknown' => 'integer',
    );

    /**
     * Return all subnets, including statistics
     *
     * @param string $order Property to sort by, default: null
     * @param string $direction One of [asc|desc].
     * @return \Model_Subnet[]
     */
    public function fetchAll($order=null, $direction='asc')
    {
        $db = Model_Database::getAdapter();

        $numIdentified = $db->select()
            ->from(
                'netmap',
                new Zend_Db_Expr('COUNT(mac)')
            )
            ->where('netid = networks.ipsubnet')
            ->where('mask = networks.ipmask')
            ->where('mac NOT IN(SELECT macaddr FROM networks)')
            ->where('mac IN(SELECT macaddr FROM network_devices)');

        $numUnknown = $db->select()
            ->from(
                'netmap',
                new Zend_Db_Expr('COUNT(mac)')
            )
            ->where('netid = networks.ipsubnet')
            ->where('mask = networks.ipmask')
            ->where('mac NOT IN(SELECT macaddr FROM networks)')
            ->where('mac NOT IN(SELECT macaddr FROM network_devices)');

        $select = $db->select()
            ->from(
                'networks',
                array(
                    'ipsubnet',
                    'ipmask',
                    'num_inventoried' => new Zend_Db_Expr('COUNT(ipmask)'),
                    'num_identified' => "($numIdentified)",
                    'num_unknown' => "($numUnknown)",
                )
            )
            ->joinLeft(
                'subnet',
                'networks.ipsubnet=subnet.netid AND networks.ipmask=subnet.mask',
                'name'
            )
            ->where('ipsubnet != \'0.0.0.0\'')
            ->where('ipsubnet != \'127.0.0.0\'')
            ->where('description NOT LIKE \'%PPP%\'')
            ->group(array('ipsubnet', 'ipmask', 'name'))
            ->order(self::getOrder($order, $direction, $this->_propertyMap));

        return $this->_fetchAll($select->query());
    }

    /** {@inheritdoc} */
    public function getProperty($property, $rawValue=false)
    {
        if ($property == 'AddressWithMask') {
            // add short notation, i.e. 255.255.255.0 becomes /24
            return $this->getAddress() . self::getCidrSuffix($this->getMask());
        } else {
            return parent::getProperty($property, $rawValue);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Address and Mask are validated. Name gets written to the database.
     *
     * @throws \InvalidArgumentException if IP address is invalid
     */
    public function setProperty($property, $value)
    {
        switch ($property) {
            case 'Address':
            case 'Mask':
                if (ip2long($value) === false) {
                    throw new \InvalidArgumentException(
                        'Not an IPv4 address: ' . $value
                    );
                }
                break;
            case 'Name':
                if ($value != $this->getName()) {
                    // Force NULL instead of empty string to maintain correct sorting order
                    if (empty($value)) {
                        $value = null;
                    }
                    $db = Model_Database::getAdapter();
                    if (!$db->update(
                        'subnet',
                        array('name' => $value),
                        array(
                            'netid = ?' => $this->getAddress(),
                            'mask = ?' => $this->getMask()
                            )
                    )) {
                        $db->insert(
                            'subnet',
                            array(
                                'netid' => $this->getAddress(),
                                'mask' => $this->getMask(),
                                'name' => $value,
                            )
                        );
                    }
                }
        }
        parent::setProperty($property, $value);
    }

    /**
     * Return the datatypes of all properties
     *
     * Add types of calculated properties that are not part of the property map.
     */
    public function getPropertyTypes()
    {
        if (empty($this->_allTypes)) { // build _allTypes only once
            parent::getPropertyTypes();
            $this->_allTypes['AddressWithMask'] = 'text';
        }
        return $this->_allTypes;
    }

    /**
     * Compose ORDER BY clause from logical identifier
     *
     * Adds support for AddressWithMask property
     */
    static function getOrder($order, $direction, $propertyMap)
    {
        if ($order == 'AddressWithMask') {
            return "ipsubnet $direction, ipmask $direction";
        } else {
            return parent::getOrder($order, $direction, $propertyMap);
        }
    }

    /**
     * Static helper method to get CIDR suffix from netmask
     * @param string $mask IPv4 mask in dotted notation, example: 255.255.255.0
     * @return string CIDR suffix, example: /24
     */
    public static function getCidrSuffix($mask)
    {
        $mask = ip2long($mask);
        if ($mask != 0) { // Next line would not work for /0 (0.0.0.0)
            $mask = 32 - log(($mask ^ 0xffffffff) + 1, 2);
        }
        return '/' . $mask;
    }

    /**
     * Create a Model_Subnet object with given properties
     *
     * Use this method to access a subnet with given address and mask. If a name
     * is defined for this subnet, it is set up for the returned object.
     *
     * @param string $address Network address
     * @param string $mask Netmask
     * @return Model_Subnet
     **/
    public function create($address, $mask)
    {
        $subnet = clone $this;
        $subnet['Address'] = $address;
        $subnet['Mask'] = $mask;
        $name = Model_Database::getAdapter()->fetchOne(
            'SELECT name FROM subnet WHERE netid = ? AND mask = ?',
            array($address, $mask)
        );
        if ($name === false) {
            $subnet->name = null;
        } else {
            $subnet->name = $name;
        }
        unset($subnet['NumInventoried']);
        unset($subnet['NumIdentified']);
        unset($subnet['NumUnknown']);

        return $subnet;
    }
}
