<?php
/**
 * Class representing a subnet
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
}
