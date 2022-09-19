<?php

/**
 * Tests for the Config class
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

namespace Database\Test\Table;

/**
 * Tests for the Config class
 */
class ConfigTest extends AbstractTest
{
    /**
     * Tests for getDbIdentifier()
     */
    public function testGetDbIdentifier()
    {
        $this->assertEquals('FREQUENCY', static::$_table->getDbIdentifier('inventoryInterval'));
        $this->expectException('InvalidArgumentException');
        static::$_table->getDbIdentifier('Invalid');
    }

    /**
     * Tests for get()
     */
    public function testGet()
    {
        // Test populated ivalue and tvalue options (check return type for integer options)
        $this->assertSame(42, static::$_table->get('inventoryInterval'));
        $this->assertSame(2048, static::$_table->get('defaultMaxFragmentSize'));
        $this->assertSame(1, static::$_table->get('defaultWarnAllowAbort'));
        $this->assertSame(0, static::$_table->get('defaultWarnAllowDelay'));
        $this->assertEquals('/example/package/path', static::$_table->get('packagePath'));
        // Test unpopulated option
        $this->assertNull(static::$_table->get('contactInterval'));
        // Test invalid option
        $this->expectException('InvalidArgumentException');
        static::$_table->get('invalid');
    }

    public function testSetValid()
    {
        $this->assertSame(false, static::$_table->set('inventoryInterval', 42)); // unchanged
        $this->assertSame(true, static::$_table->set('contactInterval', 10)); // new
        $this->assertSame(true, static::$_table->set('packagePath', '/other/package/path')); // updated
        $this->assertSame(true, static::$_table->set('inspectRegistry', true)); // ivalue true, updated
        $this->assertSame(true, static::$_table->set('saveRawData', false)); // ivalue false, updated
        $this->assertSame(true, static::$_table->set('sessionRequired', true)); // ivalue true, new
        $this->assertSame(true, static::$_table->set('trustedNetworksOnly', false)); // ivalue false, new
        $this->assertTablesEqual(
            $this->loadDataSet('Set')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetIntegerEmptyStringToNull()
    {
        static::$_table->set('scannersPerSubnet', '');
        $this->assertTablesEqual(
            $this->loadDataSet('SetIntegerEmptyStringToNull')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetStringColumnFromBooleanFalse()
    {
        static::$_table->set('defaultWarn', false);
        $this->assertSame(
            '0',
            static::$_table->select(array('name' => 'BRAINTACLE_DEFAULT_WARN'))->current()['tvalue']
        );
    }

    public function testSetStringColumnFromBooleanTrue()
    {
        static::$_table->set('defaultWarn', true);
        $this->assertSame(
            '1',
            static::$_table->select(array('name' => 'BRAINTACLE_DEFAULT_WARN'))->current()['tvalue']
        );
    }

    public function testSetInvalidOption()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid option: invalid');
        static::$_table->set('invalid', 0);
    }

    public function testSetInvalidValue()
    {
        $this->expectException(
            'InvalidArgumentException',
            'Tried to set non-integer value "invalid" to integer option "inventoryInterval"'
        );
        static::$_table->set('inventoryInterval', 'invalid');
    }

    public function testGetLimitInventoryIntervalDisabled()
    {
        static::$_table->insert(
            array(
                'name' => 'INVENTORY_FILTER_FLOOD_IP',
                'ivalue' => 0,
            )
        );
        static::$_table->insert(
            array(
                'name' => 'INVENTORY_FILTER_FLOOD_IP_CACHE_TIME',
                'ivalue' => 42,
            )
        );
        $this->assertNull(static::$_table->get('limitInventoryInterval'));
    }

    public function testGetLimitInventoryIntervalEnabled()
    {
        static::$_table->insert(
            array(
                'name' => 'INVENTORY_FILTER_FLOOD_IP',
                'ivalue' => 1,
            )
        );
        static::$_table->insert(
            array(
                'name' => 'INVENTORY_FILTER_FLOOD_IP_CACHE_TIME',
                'ivalue' => 42,
            )
        );
        $this->assertSame(42, static::$_table->get('limitInventoryInterval'));
    }

    public function testSetLimitInventoryIntervalDisabled()
    {
        static::$_table->set('limitInventoryInterval', '0');
        $this->assertSame(
            '0',
            (string) static::$_table->select(array('name' => 'INVENTORY_FILTER_FLOOD_IP'))->current()['ivalue']
        );
    }

    public function testSetLimitInventoryIntervalEnabled()
    {
        static::$_table->set('limitInventoryInterval', '42');
        $this->assertSame(
            '1',
            (string) static::$_table->select(array('name' => 'INVENTORY_FILTER_FLOOD_IP'))->current()['ivalue']
        );
    }
}
