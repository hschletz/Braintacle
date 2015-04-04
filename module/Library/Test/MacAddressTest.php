<?php
/**
 * Tests for the MacAddress class
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

namespace Library\Test;

use \Library\MacAddress;

/**
 * Tests for the MacAddress class
 */
class MacAddressTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadVendorDatabase()
    {
        $input = array(
            '# comment', //ignore
            '', //ignore
            "00:00:5e:00:53:00\tshort1", // strip colons
            "00-00-5e-00-53-01\tshort2 # long2", // strip dashes
            "00005e005302\tshort3    # long3 ", // strip whitespace from short name
            "ab:cd:ef\tshort4", // length: 6 (autodetect)
            "123456/36\tshort5", // length: 9, pad to 123456000
            "123456789abc/36\tshort6", // length: 9, truncate to 123456789
        );
        MacAddress::loadVendorDatabase($input);

        $expected = array(
            array(
                'address' => '00005E005300',
                'length' => 12,
                'vendor' => 'short1',
            ),
            array(
                'address' => '00005E005301',
                'length' => 12,
                'vendor' => 'long2',
            ),
            array(
                'address' => '00005E005302',
                'length' => 12,
                'vendor' => 'long3 ',
            ),
            array(
                'address' => 'ABCDEF',
                'length' => 6,
                'vendor' => 'short4',
            ),
            array(
                'address' => '123456000',
                'length' => 9,
                'vendor' => 'short5',
            ),
            array(
                'address' => '123456789',
                'length' => 9,
                'vendor' => 'short6',
            ),
        );
        $reflectionClass = new \ReflectionClass('Library\MacAddress');
        $this->assertEquals($expected, $reflectionClass->getStaticProperties()['_vendorList']);
    }

    public function testLoadVendorDatabaseBadMask()
    {
        // Disable PHPUnit's error handler which would stop execution upon the
        // first error.
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        // Disable all console error output
        $displayErrors = ini_get('display_errors');
        $logErrors = ini_get('log_errors');
        ini_set('display_errors', false);
        ini_set('log_errors', false);
        if (extension_loaded('xdebug')) {
            xdebug_disable();
        }

        // Invoke the tested method
        $input = array(
            "13/37\tshort7", // should trigger E_USER_NOTICE
            "00:00:5E:00:53:00\tshort", // parsing should continue
        );
        MacAddress::loadVendorDatabase($input);

        // Restore error handling
        \PHPUnit_Framework_Error_Notice::$enabled = true;
        if (ini_get('xdebug.default_enable')) {
            xdebug_enable();
        }
        ini_set('display_errors', $displayErrors);
        ini_set('log_errors', $logErrors);

        // Test the generated error
        $lastError = error_get_last();
        $this->assertEquals(E_USER_NOTICE, $lastError['type']);
        $this->assertEquals(
            'Ignoring MAC address 13/37 because mask is not a multiple of 4.',
            $lastError['message']
        );

        // Parsing should continue after error.
        $expected = array(
            array(
                'address' => '00005E005300',
                'length' => 12,
                'vendor' => 'short',
            ),
        );
        $reflectionClass = new \ReflectionClass('Library\MacAddress');
        $this->assertEquals($expected, $reflectionClass->getStaticProperties()['_vendorList']);
    }

    public function testLoadVendorDatabaseFromFile()
    {
        // Clear database first to ensure that data actually gets loaded
        MacAddress::loadVendorDatabase(array());
        // Pass default database. It should load without errors.
        MacAddress::loadVendorDatabaseFromFile(\Library\Module::getPath('data/MacAddress/manuf'));
        $reflectionClass = new \ReflectionClass('Library\MacAddress');
        $this->assertNotEmpty($reflectionClass->getStaticProperties()['_vendorList']);
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
        $vendors = array(
            "00:00:5E\tshort1",
            "00:00:5e:00:53\tshort2",
            "00:00:5E:00\tshort3",
        );
        MacAddress::loadVendorDatabase($vendors);
        $addr = new MacAddress('00:00:5E:00:53:00');
        $this->assertEquals('short2', $addr->getVendor());
    }

    public function testGetVendorLoadsDatabase()
    {
        // Reset database to force loading from file
        MacAddress::loadVendorDatabase(array());
        $addr = new MacAddress('00:00:5E:00:53:00');
        $this->assertEquals('USC INFORMATION SCIENCES INST', $addr->getVendor());
    }
}
