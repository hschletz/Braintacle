<?php

/**
 * Tests for the MacAddress class
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

namespace Library\Test;

use Library\MacAddress;

/**
 * Tests for the MacAddress class
 */
class MacAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadVendorDatabase()
    {
        $input = array(
            "#\tcomment", // ignore. The Regex alone would not filter this.
            '', // ignore
            "00:00:5e:00:53:00\tshort1", // strip colons
            "00-00-5e-00-53-01\tshort2 # long2", // strip dashes
            "00005e005302\tshort3    # long3 ", // no delimiters
            "00005E005303\tshort4    # long4 ", // uppercase
            "01:02\tshort5", // length: 16 (autodetect)
            "01:02:ab:cd\tshort6", // length: 32 (autodetect)
            "12:34:56:00:00:00/24\tshort7", // explicit mask
            "abcdef/24\tshort8", // explicit mask, pad to abcdef000000
            "deadbeef0000/40\tshort9", // explicit mask, zeroes part of prefix
            "12:34:00:00:00:00/17\tshort10", // unaligned mask
        );

        $expected = array(
            array(
                'address' => '5e005300',
                'mask' => 'ffffffffffff',
                'vendor' => 'short1',
            ),
            array(
                'address' => '5e005301',
                'mask' => 'ffffffffffff',
                'vendor' => 'long2',
            ),
            array(
                'address' => '5e005302',
                'mask' => 'ffffffffffff',
                'vendor' => 'long3 ',
            ),
            array(
                'address' => '5e005303',
                'mask' => 'ffffffffffff',
                'vendor' => 'long4 ',
            ),
            array(
                'address' => '10200000000',
                'mask' => 'ffff00000000',
                'vendor' => 'short5',
            ),
            array(
                'address' => '102abcd0000',
                'mask' => 'ffffffff0000',
                'vendor' => 'short6',
            ),
            array(
                'address' => '123456000000',
                'mask' => 'ffffff000000',
                'vendor' => 'short7',
            ),
            array(
                'address' => 'abcdef000000',
                'mask' => 'ffffff000000',
                'vendor' => 'short8',
            ),
            array(
                'address' => 'deadbeef0000',
                'mask' => 'ffffffffff00',
                'vendor' => 'short9',
            ),
            array(
                'address' => '123400000000',
                'mask' => 'ffff80000000',
                'vendor' => 'short10',
            ),
        );

        MacAddress::loadVendorDatabase($input);

        // Builtin assertions don't work with GMP objects. Iterate and compare manually.
        $vendorList = MacAddress::getVendorDatabase();
        $this->assertCount(count($expected), $vendorList);
        foreach ($vendorList as $key => $entry) {
            $expectedEntry = $expected[$key];
            $this->assertCount(count($expectedEntry), $entry);
            if (PHP_INT_SIZE < 8) {
                $this->assertEquals($expectedEntry['address'], gmp_strval($entry['address'], 16));
                $this->assertEquals($expectedEntry['mask'], gmp_strval($entry['mask'], 16));
            } else {
                $this->assertEquals(hexdec($expectedEntry['address']), $entry['address']);
                $this->assertEquals(hexdec($expectedEntry['mask']), $entry['mask']);
            }
            $this->assertEquals($expectedEntry['vendor'], $entry['vendor']);
        }
    }

    public function testLoadVendorDatabaseFromFile()
    {
        // Clear database first to ensure that data actually gets loaded
        MacAddress::loadVendorDatabase([]);
        // Pass default database. It should load without errors.
        MacAddress::loadVendorDatabaseFromFile(\Library\Module::getPath('data/MacAddress/manuf'));
        $this->assertNotEmpty(MacAddress::getVendorDatabase());
    }

    public function testToSting()
    {
        $addr = new MacAddress('00:00:5e:00:53:00'); // converted to uppercase
        $this->assertEquals('00:00:5E:00:53:00', (string) $addr);
    }

    public function testGetAddress()
    {
        $addr = new MacAddress('00:00:5e:00:53:00'); // converted to uppercase
        $this->assertEquals('00:00:5E:00:53:00', $addr->getAddress());
    }

    public function testGetVendor()
    {
        // Address range exceeds 32 bit limit to test proper implementation on 32 bit systems
        $vendors = array(
            "00:01:5E\tshort1",
            "00:01:5E:00:53\tshort2",
            "00:01:5E:00\tshort3",
        );
        MacAddress::loadVendorDatabase($vendors);
        $addr = new MacAddress('00:01:5E:00:53:01');
        $this->assertEquals('short2', $addr->getVendor());
    }

    public function testGetVendorNoMatch()
    {
        $vendors = array(
            "00:01:5E\tshort1",
            "00:01:5E:00:53\tshort2",
            "00:01:5E:00\tshort3",
        );
        MacAddress::loadVendorDatabase($vendors);
        $addr = new MacAddress('00:00:00:00:00:00');
        $this->assertNull($addr->getVendor());
    }

    public function testGetVendorLoadsDatabase()
    {
        // Reset database to force loading from file
        MacAddress::loadVendorDatabase(array());
        $addr = new MacAddress('00:00:5E:00:53:00');
        $this->assertEquals('ICANN, IANA Department', $addr->getVendor());
    }
}
