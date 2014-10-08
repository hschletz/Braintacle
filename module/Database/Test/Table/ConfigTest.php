<?php
/**
 * Tests for the Config class
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
        $this->setExpectedException('InvalidArgumentException');
        static::$_table->getDbIdentifier('Invalid');
    }

    /**
     * Tests for get()
     */
    public function testGet()
    {
        // Test populated ivalue and tvalue options
        $this->assertEquals(42, static::$_table->get('inventoryInterval'));
        $this->assertEquals('/example/log/path', static::$_table->get('logPath'));
        // Test unpopulated option
        $this->assertNull(static::$_table->get('contactInterval'));
        // Test invalid option
        $this->setExpectedException('InvalidArgumentException');
        static::$_table->get('invalid');
    }

    public function testSetValid()
    {
        $this->assertSame(false, static::$_table->set('inventoryInterval', 42)); // unchanged
        $this->assertSame(true, static::$_table->set('contactInterval', 10)); // new
        $this->assertSame(true, static::$_table->set('logPath', '/other/log/path')); // updated
        $this->assertSame(true, static::$_table->set('inspectRegistry', true)); // ivalue true, updated
        $this->assertSame(true, static::$_table->set('saveRawData', false)); // ivalue false, updated
        $this->assertSame(true, static::$_table->set('sessionRequired', true)); // ivalue true, new
        $this->assertSame(true, static::$_table->set('trustedNetworksOnly', false)); // ivalue false, new
        $this->assertTablesEqual(
            $this->_loadDataSet('Set')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
    }

    public function testSetInvalidOption()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid option: invalid');
        static::$_table->set('invalid', 0);
    }

    public function testSetInvalidValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Tried to set non-integer value "invalid" to integer option "inventoryInterval"'
        );
        static::$_table->set('inventoryInterval', 'invalid');
    }
}
