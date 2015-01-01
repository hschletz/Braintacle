<?php
/**
 * Class representing a MAC address
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
 * @package Library
 */
/**
 * Class that represents a MAC address
 *
 * The address is stored in a canonicalized form to make string operations
 * successful. For basic usage, just pass a MAC address to the constructor:
 *
 * <code>$address = new Braintacle_MacAddress('01:23:45:67:89:ab');</code>
 *
 * The Class implements the magic __tostring() method, so that it can be used
 * as a string:
 *
 * <code>print $address;</code>
 *
 * The address is always returned in the common form of 6 pairs of hexadecimal
 * digits in uppercase characters. This is important to know when
 * case-sensitive operations are performed on the address. For this reason,
 * this class should always be used to canonicalize an address before use.
 *
 * The class also provides some utility methods, like retrieving the vendor
 * from a database.
 * @package Library
 */
class Braintacle_MacAddress
{

    /**
     * The already canonicalized address
     * @var string
     */
    protected $_address;

    /**
     * The database of address-vendor relationships
     *
     * This is made static so that the database has to be loaded only once.
     * @var array
     */
    protected static $_vendorList;

    /**
     * Constructor
     *
     * The address gets canonicalized. No further validation is performed.
     * @param string $address MAC address
     */
    function __construct($address)
    {
        $this->_address = strtoupper($address);
    }

    /**
     * Return the address as a string
     */
    function __tostring()
    {
        return $this->_address;
    }

    /**
     * Load the database of address-vendor relationships.
     * A copy of a text file from the Wireshark project, located in
     * APPLICATION_PATH/configs/macaddresses-vendors.txt, is used as source.
     * The file gets parsed only once, so it is safe to call this method
     * multiple times without hurting performance.
     */
    static function loadVendorList()
    {
        if (is_array(self::$_vendorList)) {
            return; // Already loaded
        }

        $input = fopen(APPLICATION_PATH . '/configs/macaddresses-vendors.txt', 'r');
        while ($line = fgets($input)) {
            /* This regular expression matches lines of the following pattern:

               <MAC address><TAB><short name>[<whitespace># <long name>]

               Matching lines get returned as an array with exaxtly 5 elements:
               [0] unused
               [1] MAC address
               [2] short name (used if [4] is empty)
               [3] unused
               [4] long name or empty string
            */
            preg_match("/^(\H+)\t(\H+)\h*(# )?(.*)/", $line, $data);
            if (empty($data)) {
                continue;
            }
            // remove ':' and '-' delimiters
            $mac = str_replace(array(':', '-'), '', $data[1]);
            // extract bitmask if present
            $pos = strpos($mac, '/');
            if ($pos !== false) {
                $mask = substr($mac, $pos + 1);
                if ($mask % 4 != 0) {
                    // The precision of this string-based implementation is
                    // limited to 4 bits (1 hex digit). Entries with a number
                    // of mask bits that is not a multiple of 4 cannot be
                    // matched and are ignored.
                    continue;
                }
                $numDigits = $mask / 4;
                $mac = substr($mac, 0, $pos);
                $mac = str_pad($mac, $numDigits, '0'); // Fill with zeroes if too short
                $mac = substr($mac, 0, $numDigits); // Crop to maximun length if too long
            } else {
                $numDigits = strlen($mac);
            }

            $vendor = rtrim($data[4]);
            if (empty($vendor)) {
                $vendor = $data[2];
            }

            self::$_vendorList[] = array(
                'address' => strtoupper($mac),
                'mask' => $numDigits,
                'vendor' => rtrim($vendor)
            );
        }
    }

    /**
     * Return the vendor for this address.
     * It is not necessary to call loadVendorList() before using this.
     * @return string Vendor or NULL if the address is not found in the database.
     */
    public function getVendor()
    {
        self::loadVendorList();

        $mac = str_replace(':', '', $this->_address);
        $longestMask = 0;
        $vendor = null;
        foreach (self::$_vendorList as $entry) {
            $mask = $entry['mask'];
            // Compare strings only if this entry is more specific than the
            // last matching one. The === operator is necessary to prevent
            // implicit casts that would lead to false positives with
            // "00:00:00" and similar.
            if ($mask > $longestMask and substr($mac, 0, $mask) === $entry['address']) {
                $vendor = $entry['vendor'];
                $longestMask = $mask;
            }
        }
        return $vendor;
    }

    /**
     * Return a string with address and vendor for this address.
     * It is not necessary to call loadVendorList() before using this.
     * @return string "address (Vendor)" or just the the address if it is not found in the database.
     */
    public function getAddressWithVendor()
    {
        $vendor = $this->getVendor();
        if ($vendor) {
            return "$this->_address ($vendor)";
        } else {
            return $this->_address;
        }
    }
}
