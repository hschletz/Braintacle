<?php
/**
 * Tests for Model\Config
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

namespace Model\Test;

/**
 * Tests for Model\Config
 */
class ConfigTest extends AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Config', 'PackageDownloadInfo');

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
        $config = $this->_getModel();

        // Test populated ivalue and tvalue options
        $this->assertEquals(42, $config->inventoryInterval);
        $this->assertEquals('/example/log/path', $config->logPath);
        // Test default for unpopulated option
        $this->assertEquals(12, $config->contactInterval);
        // Test invalid option
        $this->setExpectedException('InvalidArgumentException');
        $config->invalid;
    }

    public function testMagicSet()
    {
        $config = $this->_getModel();

        $config->inventoryInterval = 42; // unchanged
        $config->contactInterval = 10; // new
        $config->logPath = '/other/log/path'; // updated
        $config->inspectRegistry = true; // ivalue true, updated
        $config->scanAlways = false; // ivalue false, updated
        $config->sessionRequired = true; // ivalue true, new
        $config->trustedNetworksOnly = false; // ivalue false, new

        $dataSet = $this->_loadDataSet('MagicSet');
        $this->assertTablesEqual(
            $dataSet->getTable('config'),
            $this->getConnection()->createQueryTable('config', 'SELECT * FROM config ORDER BY name')
        );
        $this->assertTablesEqual(
            $dataSet->getTable('download_enable'),
            $this->getConnection()->createQueryTable('download_enable', 'SELECT * FROM download_enable')
        );
    }

    public function testMagicSetPackageOptions()
    {
        $config = $this->_getModel();

        $config->packageCertificate = 'path_new/file_new';
        $config->packageBaseUriHttps = 'https_new';
        $config->packageBaseUriHttp = 'http_new';
        $this->assertTablesEqual(
            $this->_loadDataSet('MagicSetPackageOptions')->getTable('download_enable'),
            $this->getConnection()->createQueryTable('download_enable', 'SELECT * FROM download_enable')
        );
    }

    public function testMagicSetInvalidOption()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid option: invalid');
        $this->_getModel()->invalid = 0;
    }

    public function testMagicSetInvalidValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Tried to set non-integer value "invalid" to integer option "inventoryInterval"'
        );
        $this->_getModel()->inventoryInterval = 'invalid';
    }
}
