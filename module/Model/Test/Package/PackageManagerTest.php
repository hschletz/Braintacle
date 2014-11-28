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
use org\bovigo\vfs\vfsStream;

/**
 * Tests for Model\Package\PackageManager
 */
class PackageManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Packages', 'PackageDownloadInfo', 'ClientConfig');

    public function testPackageExists()
    {
        $this->assertTrue($this->_getModel()->packageExists('package1'));
        $this->assertFalse($this->_getModel()->packageExists('new_package'));
    }

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
                'SELECT id, fileid, info_loc, pack_loc, cert_path, cert_file FROM download_enable ORDER BY fileid'
            )
        );

    }

    public function testAutoArchiveWindowsCreateArchiveKeepSource()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = array('FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows');
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(\Library\ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(\Library\ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(\Library\ArchiveManager::ZIP, 'path/archive')
                       ->willReturn('archive');
        $archiveManager->expects($this->once())
                       ->method('addFile')
                       ->with('archive', $source, 'FileName');
        $archiveManager->expects($this->once())
                       ->method('closeArchive')
                       ->with('archive', false);
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        $this->assertEquals('path/archive', $model->autoArchive($data, 'path', false));
        $this->assertFileExists($source);
    }

    public function testAutoArchiveWindowsCreateArchiveDeleteSource()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = array('FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows');
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(\Library\ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(\Library\ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(\Library\ArchiveManager::ZIP, 'path/archive')
                       ->willReturn('archive');
        $archiveManager->expects($this->once())
                       ->method('addFile')
                       ->with('archive', $source, 'FileName');
        $archiveManager->expects($this->once())
                       ->method('closeArchive')
                       ->with('archive', false);
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        $this->assertEquals('path/archive', $model->autoArchive($data, 'path', true));
        $this->assertFileNotExists($source);
    }

    public function testAutoArchiveWindowsErrorOnArchiveCreation()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = array('FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows');
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(\Library\ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(\Library\ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(\Library\ArchiveManager::ZIP, 'path/archive')
                       ->will($this->throwException(new \RuntimeException('createArchive')));
        $archiveManager->expects($this->never())
                       ->method('addFile');
        $archiveManager->expects($this->never())
                       ->method('closeArchive');
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        try {
            $model->autoArchive($data, 'path', true);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('createArchive', $e->getMessage());
            $this->assertFileExists($source);
        }
    }

    public function testAutoArchiveWindowsErrorAfterArchiveCreation()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $archive = $root->url() . '/archive';
        $data = array('FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows');
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(\Library\ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(\Library\ArchiveManager::ZIP, $source)
                       ->willReturn(false);
        $archiveManager->expects($this->once())
                       ->method('createArchive')
                       ->with(\Library\ArchiveManager::ZIP, $archive)
                       ->will(
                           $this->returnCallback(
                               function () use ($root) {
                                   return vfsStream::newFile('archive')->at($root)->url();
                               }
                           )
                       );
        $archiveManager->expects($this->once())
                       ->method('addFile')
                       ->with($archive, $source, 'FileName');
        $archiveManager->expects($this->at(4))
                       ->method('closeArchive')
                       ->with($archive, false)
                       ->will($this->throwException(new \RuntimeException('closeArchive')));
        $archiveManager->expects($this->at(5))
                       ->method('closeArchive')
                       ->with($archive, true);
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        try {
            $model->autoArchive($data, $root->url(), true);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('closeArchive', $e->getMessage());
            $this->assertFileExists($source);
            $this->assertFileNotExists($archive);
        }
    }

    public function testAutoArchiveWindowsAlreadyArchive()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = array('FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows');
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(\Library\ArchiveManager::ZIP)
                       ->willReturn(true);
        $archiveManager->expects($this->once())
                       ->method('isArchive')
                       ->with(\Library\ArchiveManager::ZIP, $source)
                       ->willReturn(true);
        $archiveManager->expects($this->never())
                       ->method('createArchive');
        $archiveManager->expects($this->never())
                       ->method('addFile');
        $archiveManager->expects($this->never())
                       ->method('closeArchive');
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        $this->assertEquals($source, $model->autoArchive($data, 'path', true));
        $this->assertFileExists($source);
    }

    public function testAutoArchiveWindowsArchiveNotSupported()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = array('FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'windows');
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->once())
                       ->method('isSupported')
                       ->with(\Library\ArchiveManager::ZIP)
                       ->willReturn(false);
        $archiveManager->expects($this->never())
                       ->method('isArchive');
        $archiveManager->expects($this->never())
                       ->method('createArchive');
        $archiveManager->expects($this->never())
                       ->method('addFile');
        $archiveManager->expects($this->never())
                       ->method('closeArchive');
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        $this->assertEquals($source, @$model->autoArchive($data, 'path', true));
        $this->assertFileExists($source);
    }

    public function testAutoArchiveUnsupportedPlatform()
    {
        $root = vfsstream::setup('root');
        $source = vfsStream::newFile('source')->at($root)->url();
        $data = array('FileLocation' => $source, 'FileName' => 'FileName', 'Platform' => 'linux');
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->never())
                       ->method('isSupported');
        $archiveManager->expects($this->never())
                       ->method('isArchive');
        $archiveManager->expects($this->never())
                       ->method('createArchive');
        $archiveManager->expects($this->never())
                       ->method('addFile');
        $archiveManager->expects($this->never())
                       ->method('closeArchive');
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        $this->assertEquals($source, @$model->autoArchive($data, 'path', true));
        $this->assertFileExists($source);
    }

    public function testAutoArchiveNoSourceFile()
    {
        $source = '';
        $data = array('FileLocation' => $source);
        $archiveManager = $this->getMock('Library\ArchiveManager');
        $archiveManager->expects($this->never())
                       ->method('isSupported');
        $archiveManager->expects($this->never())
                       ->method('isArchive');
        $archiveManager->expects($this->never())
                       ->method('createArchive');
        $archiveManager->expects($this->never())
                       ->method('addFile');
        $archiveManager->expects($this->never())
                       ->method('closeArchive');
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        $this->assertSame($source, @$model->autoArchive($data, 'path', true));
    }

    public function testDelete()
    {
        $data = array('Timestamp' => new \Zend_Date(1415958319, \Zend_Date::TIMESTAMP));
        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();
        $storage->expects($this->once())->method('cleanup')->with($data);
        $model = $this->_getModel(array('Model\Package\Storage\Direct' => $storage));
        $model->delete($data);

        $connection = $this->getConnection();
        $dataset = $this->_loadDataSet('Delete');
        $this->assertTablesEqual(
            $dataset->getTable('download_available'),
            $connection->createQueryTable('download_available', 'SELECT * FROM download_available ORDER BY fileid')
        );
        $this->assertTablesEqual(
            $dataset->getTable('download_enable'),
            $connection->createQueryTable(
                'download_enable',
                'SELECT id, fileid, info_loc, pack_loc, cert_path, cert_file FROM download_enable ORDER BY fileid'
            )
        );
        $this->assertTablesEqual(
            $dataset->getTable('devices'),
            $connection->createQueryTable('devices', 'SELECT hardware_id, name, ivalue FROM devices ORDER BY ivalue')
        );
    }
}
