<?php
/**
 * Class representing a network interface
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
/**
 * A network interface
 *
 * Properties:
 *
 * - <b>Description</b>
 * - <b>Rate</b>
 * - <b>MacAddress</b>
 * - <b>IpAddress</b>
 * - <b>Netmask</b>
 * - <b>Gateway</b>
 * - <b>Subnet</b>
 * - <b>DhcpServer</b>
 * - <b>Status</b>
 * - <b>Virtual</b>
 * @package Models
 */
class Model_NetworkInterface extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'networks' table
        'Description' => 'description',
        'Rate' => 'speed',
        'MacAddress' => 'macaddr',
        'IpAddress' => 'ipaddress',
        'Netmask' => 'ipmask',
        'Gateway' => 'ipgateway',
        'Subnet' => 'ipsubnet',
        'DhcpServer' => 'ipdhcp',
        'Status' => 'status',
        'Virtual' => 'virtualdev',
        'RawType' => 'type', // needs further processing to be really useful
        'RawTypeMib' => 'typemib', // needs further processing to be really useful
    );

    /** {@inheritdoc} */
    protected $_tableName = 'networks';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Description';

    /**
     * Retrieve a property by its logical name
     *
     * Converts MacAddres into a Braintacle_MacAddress object.
     */
    function getProperty($property, $rawValue=false)
    {
        $value = parent::getProperty($property, $rawValue);
        if ($rawValue or $property != 'MacAddress') {
            return $value;
        }
        return new Braintacle_MacAddress($value);
    }

    /**
     * Return TRUE if an interface's MAC address is blacklisted, i.e. ignored
     * for detection of duplicates.
     * @return bool
     */
    public function isBlacklisted()
    {
        $db = Model_Database::getAdapter();

        return (bool) $db->fetchOne(
            'SELECT COUNT(macaddress) FROM blacklist_macaddresses WHERE macaddress = ?',
            $this->getMacAddress()
        );
    }

}
