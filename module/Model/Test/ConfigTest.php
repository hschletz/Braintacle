<?php
/**
 * Tests for Model\Config
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Test;

/**
 * Tests for Model\Config
 */
class ConfigTest extends AbstractTest
{
    /** {@inheritdoc} */
    protected $_tables = array('Config');

    /**
     * Tests for getDbIdentifier()
     */
    public function testGetDbIdentifier()
    {
        $model = $this->_getModel();
        $this->assertEquals('FREQUENCY', $model->getDbIdentifier('inventoryInterval'));
        $this->setExpectedException('InvalidArgumentException');
        $model->getDbIdentifier('invalid');
    }

    /**
     * Tests for __get()
     */
    public function testMagicGet()
    {
        $config = clone $this->_getModel();

        // Test populated ivalue and tvalue options
        $this->assertEquals(42, $config->inventoryInterval);
        $this->assertEquals('/example/log/path', $config->logPath);
        // Test default for unpopulated option
        $this->assertEquals(12, $config->contactInterval);
        // Test invalid option
        $this->setExpectedException('InvalidArgumentException');
        $config->invalid;
    }

    /**
     * Tests for __set()
     */
    public function testMagicSet()
    {
        $config = clone $this->_getModel();

        $config->inventoryInterval = 42; // unchanged
        $config->contactInterval = 10; // new
        $config->logPath = '/other/log/path'; // updated
        $config->inspectRegistry = true; // ivalue true, updated
        $config->scanAlways = false; // ivalue false, updated
        $config->sessionRequired = true; // ivalue true, new
        $config->trustedNetworksOnly = false; // ivalue false, new
        $this->assertTablesEqual(
            $this->_loadDataSet('MagicSet')->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );

        try {
            $config->invalid = 0;
            $this->fail('Invalid option should have thrown an exception');
        } catch(\Exception $e) {
            $this->assertEquals('Invalid option: invalid', $e->getMessage());
        }

        try {
            /**/$config->inventoryInterval = 'invalid';
            $this->fail('Invalid value should have thrown an exception');
        } catch(\Exception $e) {
            $this->assertEquals(
                'Tried to set non-integer value "invalid" to integer option "inventoryInterval"',
                $e->getMessage()
            );
        }
    }
}
