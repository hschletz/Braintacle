<?php
/**
 * MAC address datatype
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

namespace Library;

/**
 * MAC address datatype
 *
 * The class operates on MAC addresses in the common notation of colon-separated
 * pairs of hexadecimal digits. Pass a MAC address to the constructor:
 *
 *     $address = new \Library\MacAddress('00:00:5e:00:53:00');
 *
 * The class implements the magic __toString() method, so that it can be used
 * as a string:
 *
 *     print $address;
 *
 * The address is always returned in uppercase characters. This is important
 * when case sensitive operations are performed on the address.
 *
 * The class also provides some utility methods, like retrieving the vendor
 * from a database.
 */
class MacAddress
{
    /**
     * The address passed to the constructor (uppercase)
     * @var string
     */
    protected $_address;

    /**
     * The database of address-vendor relationships
     *
     * This is static so that the database is shared across instances.
     * Each entry is an associative array with 3 fields:
     * - address: Address (full or partial), uppercase without separators
     * - length: Significant characters of address fragment used to match an address
     * - vendor: Vendor name
     * @var array[]
     */
    protected static $_vendorList;

    /**
     * Constructor
     *
     * @param string $address MAC address. No validation is performed.
     */
    public function __construct($address)
    {
        $this->_address = strtoupper($address);
    }

    /**
     * Alias for getAddress()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_address;
    }

    /**
     * Return the address as a string
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->_address;
    }

    /**
     * Load vendor database from a file
     *
     * @param string $fileName
     */
    public static function loadVendorDatabaseFromFile($fileName)
    {
        $input = new \Library\FileObject($fileName, 'r');
        $input->setFlags(\SplFileObject::DROP_NEW_LINE);
        self::loadVendorDatabase($input);
    }

    /**
     * Load vendor database
     *
     * Clears the database and iterates over $input. Each entry of the form
     * "MAC_address[/bits] TAB short_name [whitespace # long_name]" gets parsed
     * and added to the database.
     *
     * @param array|\Traversable $input
     */
    public static function loadVendorDatabase($input)
    {
        self::$_vendorList = array();
        foreach ($input as $line) {
            /* This regular expression matches lines of the following pattern:

               <MAC address><TAB><short name>[<whitespace># <long name>]

               Matching lines leave $data as an array with exaxtly 5 elements:
               [0] unused
               [1] MAC address with optional mask suffix ("/36")
               [2] short vendor name (used if [4] is empty)
               [3] unused
               [4] long vendor name or empty string
            */
            if (!preg_match("/^(\H+)\t(\H+)\h*(# )?(.*)/", $line, $data)) {
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
                    if (\Library\Application::isDevelopment()) {
                        trigger_error(
                            "Ignoring MAC address $data[1] because mask is not a multiple of 4.",
                            E_USER_NOTICE
                        );
                    }
                    continue;
                }
                $numDigits = $mask / 4;
                $mac = substr($mac, 0, $pos);
                $mac = str_pad($mac, $numDigits, '0'); // Fill with zeroes if too short
                $mac = substr($mac, 0, $numDigits); // Crop to maximun length if too long
            } else {
                $numDigits = strlen($mac);
            }

            self::$_vendorList[] = array(
                'address' => strtoupper($mac),
                'length' => $numDigits,
                'vendor' => $data[4] ?: $data[2],
            );
        }
    }

    /**
     * Return the vendor for this address.
     *
     * If the database is empty, the default database is loaded.
     *
     * @return string Vendor or NULL if the address is not found in the database.
     */
    public function getVendor()
    {
        if (empty(self::$_vendorList)) {
            self::loadVendorDatabaseFromFile(\Library\Module::getPath('data/MacAddress/manuf'));
        }
        $addr = str_replace(':', '', $this->_address);
        $longest = 0;
        $vendor = null;
        foreach (self::$_vendorList as $entry) {
            $length = $entry['length'];
            // Compare strings only if this entry is more specific than the
            // last matching one. The === operator is necessary to prevent
            // implicit casts that would lead to false positives with
            // "00:00:00" and similar.
            if ($length > $longest and substr($addr, 0, $length) === $entry['address']) {
                $vendor = $entry['vendor'];
                $longest = $length;
            }
        }
        return $vendor;
    }
}
