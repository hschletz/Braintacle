<?php

/**
 * MAC address datatype
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
     * The length of a MAC address in bits
     */
    const LENGTH_BITS = 48;

    /**
     * The length of a MAC address in hex digits
     */
    const LENGTH_HEX = 12;

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
     * - address: Address as 48 bit integer
     * - mask: bitmask used to match an address
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
     * Get the global vendor database.
     *
     * @return array
     */
    public static function getVendorDatabase(): array
    {
        return static::$_vendorList;
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
            // The regex produces a $matches array with these elements of interest:
            // [1] MAC address or prefix, with optional mask suffix ("/36")
            // [2] short vendor name (used if [4] is empty)
            // [4] long vendor name or empty string
            if (
                $line == '' or
                $line[0] == '#' or
                !preg_match("/^(\H+)\t(\H+)\h*(# )?(.*)/", $line, $matches)
            ) {
                continue;
            }
            // remove ':' and '-' delimiters
            $mac = str_replace(array(':', '-'), '', $matches[1]);

            $pos = strpos($mac, '/');
            if ($pos === false) {
                // No mask suffix. $mac is a prefix. Derive mask length (number
                // of bits) from prefix length (1 digit => 4 bits).
                $address = $mac;
                $length = strlen($mac) * 4;
            } else {
                // Split string at delimiter. Left part is the address or
                // prefix, right part is the mask length in bits.
                $address = substr($mac, 0, $pos);
                $length = substr($mac, $pos + 1);
            }

            self::$_vendorList[] = array(
                'address' => str_pad($address, self::LENGTH_HEX, '0'),
                'mask' => $length,
                'vendor' => $matches[4] ?: $matches[2],
            );
        }

        // Convert raw data to platform-specific values. Use native 64 bit
        // integers if available, GMP objects otherwise (requires GMP
        // extension).
        // The mask length is converted to a bitmask.
        // @codeCoverageIgnoreStart
        if (PHP_INT_SIZE < 8) {
            if (!extension_loaded('gmp')) {
                throw new \ErrorException('64 bit integers not available, install GMP extension');
            }
            foreach (self::$_vendorList as &$entry) {
                $entry['address'] = gmp_init($entry['address'], 16);
                $entry['mask'] = ((gmp_init(1) << $entry['mask']) - 1) << (self::LENGTH_BITS - $entry['mask']);
            }
        } else {
            foreach (self::$_vendorList as &$entry) {
                $entry['address'] = hexdec($entry['address']);
                $entry['mask'] = ((1 << $entry['mask']) - 1) << (self::LENGTH_BITS - $entry['mask']);
            }
        }
        // @codeCoverageIgnoreEnd
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

        // @codeCoverageIgnoreStart
        if (PHP_INT_SIZE < 8) {
            $addr = gmp_init($addr, 16);
        } else {
            $addr = hexdec($addr);
        }
        // @codeCoverageIgnoreEnd

        $longest = 0;
        $vendor = null;
        foreach (self::$_vendorList as $entry) {
            $mask = $entry['mask'];
            // Compare addresses only if this entry is more specific than the
            // last matching one.
            if ($mask > $longest and ($addr & $mask) == $entry['address']) {
                $vendor = $entry['vendor'];
                $longest = $mask;
            }
        }
        return $vendor;
    }
}
