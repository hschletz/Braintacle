<?php
/**
 * Tests for Model\Package\PackageManager
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

namespace Model\Test\Package;
use Model\Package\PackageManager;

/**
 * Tests for Model\Package\PackageManager
 */
class PackageManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Packages', 'PackageDownloadInfo');

    public function buildProvider()
    {
        return array(
            array('windows', 'WINDOWS'),
            array('linux', 'LINUX'),
            array('mac', 'MacOSX'),
        );
    }

    /**
     * Test build() method
     *
     * @param string $platform Internal platform descriptor (windows, linux, mac)
     * @param mixed $platformValue Database identifier (WINDOWS, LINUX, MacOSX)
     * @dataProvider buildProvider
     */
    public function testBuild($platform, $platformValue)
    {
        $data = array(
            'Timestamp' => new \Zend_Date(1415961925, \Zend_Date::TIMESTAMP),
            'Platform' => $platform,
            'Name' => 'package_new',
            'Priority' => '7',
            'NumFragments' => '23',
            'Size' => '87654321',
            'Comment' => 'New package',
        );
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('__get')
               ->will(
                   $this->returnValueMap(
                       array(
                           array('packageBaseUriHttps', 'HTTPS URL'),
                           array('packageBaseUriHttp', 'HTTP URL'),
                           array('packageCertificate', 'path/filename'),
                       )
                   )
               );
        $model = $this->_getModel(array('Model\Config' => $config));
        $model->build($data);

        $connection = $this->getConnection();
        $dataset = new \PHPUnit_Extensions_Database_DataSet_ReplacementDataSet(
            $this->_loadDataSet('Build')
        );
        $dataset->addFullReplacement('#PLATFORM#', $platformValue);
        $this->assertTablesEqual(
            $dataset->getTable('download_available'),
            $connection->createQueryTable('download_available', 'SELECT * FROM download_available ORDER BY fileid')
        );
        $this->assertTablesEqual(
            $dataset->getTable('download_enable'),
            $connection->createQueryTable(
                'download_enable',
                'SELECT fileid, info_loc, pack_loc, cert_path, cert_file FROM download_enable ORDER BY fileid'
            )
        );
    }

    public function testBuildInvalidPlatform()
    {
        $data = array(
            'Timestamp' => new \Zend_Date,
            'Platform' => 'invalid',
            'Name' => 'package_new',
            'Priority' => '7',
            'NumFragments' => '23',
            'Size' => '87654321',
            'Comment' => 'New package',
        );
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $model = $this->_getModel(array('Model\Config' => $config));
        try {
            $model->build($data);
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Invalid platform: invalid', $e->getMessage());
        }
        $connection = $this->getConnection();
        $dataset = $this->_loadDataSet(); // unchanged
        $this->assertTablesEqual(
            $dataset->getTable('download_available'),
            $connection->createQueryTable('download_available', 'SELECT * FROM download_available ORDER BY fileid')
        );
        $this->assertTablesEqual(
            $dataset->getTable('download_enable'),
            $connection->createQueryTable(
                'download_enable',
                'SELECT fileid, info_loc, pack_loc, cert_path, cert_file FROM download_enable ORDER BY fileid'
            )
        );

    }
}
