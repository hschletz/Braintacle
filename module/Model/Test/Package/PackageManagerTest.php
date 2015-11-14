<?php
/**
 * Tests for Model\Package\PackageManager
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

namespace Model\Test\Package;
use Model\Package\PackageManager;
use org\bovigo\vfs\vfsStream;

/**
 * Tests for Model\Package\PackageManager
 */
class PackageManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Config', 'Packages', 'PackageDownloadInfo', 'ClientConfig', 'GroupInfo');

    public function testPackageExists()
    {
        $this->assertTrue($this->_getModel()->packageExists('package1'));
        $this->assertFalse($this->_getModel()->packageExists('new_package'));
    }

    public function testGetPackage()
    {
        $packageData = array(
            'Id' => '1415958320',
            'Name' => 'package2',
            'Priority' => '5',
            'NumFragments' => '42',
            'Size' => '12345678',
            'Platform' => 'linux',
            'Comment' => 'Existing package 2',
        );
        $metadata = array(
            'DeployAction' => 'DeployAction',
            'ActionParam' => 'ActionParam',
            'Warn' => 'Warn',
            'WarnMessage' => 'WarnMessage',
            'WarnCountdown' => 'WarnCountdown',
            'WarnAllowAbort' => 'WarnAllowAbort',
            'WarnAllowDelay' => 'WarnAllowDelay',
            'PostInstMessage' => 'PostInstMessage',
        );
        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();
        $storage->expects($this->once())->method('readMetadata')->with('1415958320')->willReturn($metadata);
        $model = $this->_getModel(array('Model\Package\Storage\Direct' => $storage));
        $package = $model->getPackage('package2');
        $this->assertInstanceOf('Model\Package\Package', $package);
        $this->assertEquals(
            $packageData + $metadata + array('Timestamp' => new \DateTime('@1415958320')),
            $package->getArrayCopy()
        );
    }

    public function testGetPackageInvalidName()
    {
        $this->setExpectedException('Model\Package\RuntimeException', "There is no package with name 'invalid'");
        $model = $this->_getModel();
        $model->getPackage('invalid');
    }

    public function testGetPackageError()
    {
        $this->setExpectedException('Model\Package\RuntimeException', 'metadata error');
        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();
        $storage->method('readMetadata')->will($this->throwException(new \RuntimeException('metadata error')));
        $model = $this->_getModel(array('Model\Package\Storage\Direct' => $storage));
        $model->getPackage('package1');
    }

    public function getPackagesProvider()
    {
        $package1 =  array (
            'Timestamp' => new \DateTime('@1415958319'),
            'Name' => 'package1',
            'Priority' => '5',
            'NumFragments' => '42',
            'Size' => '12345678',
            'Platform' => 'windows',
            'Comment' => 'Existing package 1',
            'Id' => '1415958319',
            'NumNonnotified' => '1',
            'NumSuccess' => '1',
            'NumNotified' => '1',
            'NumError' => '1',
        );
        $package2 =  array (
            'Timestamp' => new \DateTime('@1415958320'),
            'Name' => 'package2',
            'Priority' => '5',
            'NumFragments' => '42',
            'Size' => '12345678',
            'Platform' => 'linux',
            'Comment' => 'Existing package 2',
            'Id' => '1415958320',
            'NumNonnotified' => '1',
            'NumSuccess' => '0',
            'NumNotified' => '0',
            'NumError' => '0',
        );
        return array(
            array(null, null, $package1, $package2),
            array('Timestamp', 'asc', $package1, $package2),
            array('Timestamp', 'desc', $package2, $package1),
            array('Name', 'asc', $package1, $package2),
            array('Name', 'desc', $package2, $package1),
            array('NumSuccess', 'asc', $package2, $package1),
            array('NumSuccess', 'desc', $package1, $package2),
        );
    }

    /**
     * Test getPackages()
     *
     * @param string $order tested sort order
     * @param string $direction tested sort direction
     * @param array $package1 first package in the expected result
     * @param array $package2 second package in the expected result
     * @dataProvider getPackagesProvider
     */
    public function testGetPackages($order, $direction, $package1, $package2)
    {
        $model = $this->_getModel();
        $packages = iterator_to_array($model->getPackages($order, $direction));
        $this->assertContainsOnlyInstancesOf('Model\Package\Package', $packages);
        $this->assertEquals($package1, $packages[0]->getArrayCopy());
        $this->assertEquals($package2, $packages[1]->getArrayCopy());
    }

    public function testGetAllNames()
    {
        $model = $this->_getModel();
        $this->assertEquals(array('package1', 'package2'), $model->getAllNames());
    }

    public function testGetAllNamesEmpty()
    {
        $model = $this->_getModel();
        \Library\Application::getService('Database\Table\Packages')->delete(true);
        $this->assertEquals(array(), $model->getAllNames());
    }

    public function buildProvider()
    {
        $sourceContent = 'abcdef';
        $sourceHash = sha1($sourceContent);
        $sourceSize = strlen($sourceContent);

        $archiveContent = 'ghi';
        $archiveHash = sha1($archiveContent);
        $archiveSize = strlen($archiveContent);

        return array(
            array('windows', 'WINDOWS', $archiveContent, true, $archiveHash, $archiveSize, true, true),
            array('windows', 'WINDOWS', $archiveContent, true, $archiveHash, $archiveSize, false, true),
            array('linux', 'LINUX', $sourceContent, false, $sourceHash, $sourceSize, true, true),
            array('linux', 'LINUX', $sourceContent, false, $sourceHash, $sourceSize, false, false),
            array('mac', 'MacOSX',  null, false, null, 0, true, true),
            array('mac', 'MacOSX',  null, false, null, 0, false, false),
        );
    }

    /**
     * Test build() method
     *
     * @param string $platform Internal platform descriptor (windows, linux, mac)
     * @param mixed $platformValue Database identifier (WINDOWS, LINUX, MacOSX)
     * @param string $content File content to validate (NULL to simulate no source file and no archive)
     * @param bool $createArchive Create archive, otherwise source file is assumed to be archive
     * @param string $hash Expected hash
     * @param integer $size Expected size
     * @param bool $deleteSource Passed to build()
     * @param bool $deleteArchive Expected argument for StorageInterface::write()
     * @dataProvider buildProvider
     */
    public function testBuild(
        $platform,
        $platformValue,
        $content,
        $createArchive,
        $hash,
        $size,
        $deleteSource,
        $deleteArchive
    )
    {
        // vfsStream is difficult to set up from a data provider, so the files are created here.
        $root = vfsStream::setup('root');
        if ($content) {
            if ($createArchive) {
                $fileLocation = 'source_file';
                $archive = vfsStream::newFile('archive')->withContent($content)->at($root)->url();
            } else {
                $fileLocation = vfsStream::newFile('test')->withContent($content)->at($root)->url();
                $archive = $fileLocation;
            }
        } else {
            $fileLocation = '';
            $archive = '';
        }

        // Input data. More fields are added and tested internally
        $data = array(
            'Id' => 1423401452,
            'Platform' => $platform,
            'Name' => 'package_new',
            'Priority' => '7',
            'Comment' => 'New package',
            'FileLocation' => $fileLocation,
        );

        // Callback to test the static part of package data (input values)
        $checkStaticData = function($testData) use ($data) {
            unset($testData['Hash']);
            unset($testData['Size']);
            return ($testData === $data);
        };

        // Callback to test the added file properties
        $checkFileProperties = function($testData) use ($hash, $size) {
            return ($testData['Hash'] === $hash and $testData['Size'] === $size);
        };

        // Storage mock
        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();
        $storage->expects($this->once())
                ->method('prepare')
                ->with($this->callback($checkStaticData))
                ->willReturn('Path');
        $storage->expects($this->once())
                ->method('write')
                ->with(
                    $this->logicalAnd(
                        $this->callback($checkStaticData),
                        $this->callback($checkFileProperties)
                    ),
                    $archive,
                    $deleteArchive
                )
                ->willReturn(23);

        $packages = \Library\Application::getService('Database\Table\Packages');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Table\Packages', true, $packages),
                    array('Library\Now', true, new \DateTime('2015-02-08 14:17:32')),
                    array('Model\Package\Storage\Direct', true, $storage),
                )
            )
        );

        // Model mock
        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('packageExists', 'autoArchive', 'delete'))
                      ->setConstructorArgs(array($serviceManager))
                      ->getMock();
        $model->expects($this->once())->method('packageExists')->willReturn(false);
        $model->expects($this->once())
              ->method('autoArchive')
              ->with(
                  $this->callback($checkStaticData),
                  'Path',
                  $deleteSource
              )
              ->willReturn($archive);
        $model->expects($this->never())->method('delete');

        // Invoke build method
        $model->build($data, $deleteSource);

        // Test database results
        $connection = $this->getConnection();
        $dataset = new \PHPUnit_Extensions_Database_DataSet_ReplacementDataSet(
            $this->_loadDataSet('Build')
        );
        $dataset->addFullReplacement('#PLATFORM#', $platformValue);
        $dataset->addFullReplacement('#SIZE#', $size);
        $this->assertTablesEqual(
            $dataset->getTable('download_available'),
            $connection->createQueryTable('download_available', 'SELECT * FROM download_available ORDER BY fileid')
        );
        $this->assertTablesEqual(
            $dataset->getTable('download_enable'),
            $connection->createQueryTable(
                'download_enable',
                'SELECT fileid, info_loc, pack_loc FROM download_enable ORDER BY fileid'
            )
        );
    }

    public function testBuildInvalidPlatform()
    {
        $data = array('Name' => 'test', 'FileLocation' => null, 'Platform' => 'invalid');

        $hydrator = $this->getMock('Zend\Stdlib\Hydrator\ArraySerializable');
        $hydrator->expects($this->once())->method('extract')->willReturn(array('osname' => null));

        $packages = $this->getMockBuilder('Database\Table\Packages')->disableOriginalConstructor()->getMock();
        $packages->expects($this->once())->method('getHydrator')->willReturn($hydrator);

        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Table\Packages', true, $packages),
                    array('Library\Now', true, new \DateTime),
                    array('Model\Package\Storage\Direct', true, $storage),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('packageExists', 'autoArchive', 'delete'))
                      ->setConstructorArgs(array($serviceManager))
                      ->getMock();
        $model->method('packageExists')->with('test')->willReturn(false);
        $model->expects($this->once())->method('delete')->with('test');

        try {
            $model->build($data, false);
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Package\RuntimeException $e) {
            $this->assertEquals('Invalid platform: invalid', $e->getMessage());
        }
    }

    public function testBuildPackageExists()
    {
        $data = array(
            'Platform' => 'linux',
            'Name' => 'package1',
        );
        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();
        $storage->expects($this->never())->method('write');
        $packages = $this->getMockBuilder('Database\Table\Packages')->disableOriginalConstructor()->getMock();
        $packages->expects($this->never())->method('insert');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Table\Packages', true, $packages),
                    array('Library\Now', true, new \DateTime),
                    array('Model\Package\Storage\Direct', true, $storage),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('packageExists'))
                      ->setConstructorArgs(array($serviceManager))
                      ->getMock();
        $model->expects($this->once())->method('packageExists')->with('package1')->willReturn(true);
        try {
            $model->build($data, false);
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Package\RuntimeException $e) {
            $this->assertEquals("Package 'package1' already exists", $e->getMessage());
        }
    }

    public function buildFileErrorProvider()
    {
        return array(
            array(false, "Could not determine size of 'vfs://root/nonexistent'"),
            array(true, "Could not compute SHA1 hash of 'statonly://'"),
        );
    }

    /**
     * Test runtime errors concerning source file
     *
     * @param bool $fileExists Simulate read failure on existing file
     * @param string $message Expected exception message
     * @dataProvider buildFileErrorProvider
     */
    public function testBuildFileError($fileExists, $message)
    {
        if ($fileExists) {
            $source = 'statonly://';
        } else {
            $source = vfsStream::setup('root')->url() . '/nonexistent';
        }

        $data = array(
            'Platform' => 'linux',
            'Name' => 'package_new',
            'FileLocation' => $source,
        );

        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();
        $storage->expects($this->once())->method('prepare');
        $storage->expects($this->never())->method('write');

        $packages = $this->getMockBuilder('Database\Table\Packages')->disableOriginalConstructor()->getMock();
        $packages->expects($this->never())->method('insert');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Table\Packages', true, $packages),
                    array('Library\Now', true, new \DateTime),
                    array('Model\Package\Storage\Direct', true, $storage),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('packageExists', 'autoArchive', 'delete'))
                      ->setConstructorArgs(array($serviceManager))
                      ->getMock();
        $model->expects($this->once())->method('packageExists')->willReturn(false);
        $model->expects($this->once())->method('autoArchive')->willReturn($source);
        $model->expects($this->once())->method('delete')->with('package_new');

        $this->setExpectedException('Model\Package\RuntimeException', $message);
        $model->build($data, false);
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
                       ->will($this->throwException(new \Model\Package\RuntimeException('createArchive')));
        $archiveManager->expects($this->never())
                       ->method('addFile');
        $archiveManager->expects($this->never())
                       ->method('closeArchive');
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        try {
            $model->autoArchive($data, 'path', true);
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Package\RuntimeException $e) {
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
                       ->will($this->throwException(new \Model\Package\RuntimeException('closeArchive')));
        $archiveManager->expects($this->at(5))
                       ->method('closeArchive')
                       ->with($archive, true);
        $model = $this->_getModel(array('Library\ArchiveManager' => $archiveManager));
        try {
            $model->autoArchive($data, $root->url(), true);
            $this->fail('Expected exception was not thrown');
        } catch (\Model\Package\RuntimeException $e) {
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
        $storage = $this->getMockBuilder('Model\Package\Storage\Direct')->disableOriginalConstructor()->getMock();
        $storage->expects($this->once())->method('cleanup')->with('1415958319');
        $model = $this->_getModel(array('Model\Package\Storage\Direct' => $storage));
        $model->delete('package1');

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
                'SELECT id, fileid, info_loc, pack_loc FROM download_enable ORDER BY fileid'
            )
        );
        $this->assertTablesEqual(
            $dataset->getTable('devices'),
            $connection->createQueryTable('devices', 'SELECT hardware_id, name, ivalue FROM devices ORDER BY ivalue')
        );
    }

    public function testDeleteException()
    {
        $this->setExpectedException('Model\Package\RuntimeException', "Package 'invalid' does not exist");
        $model = $this->_getModel();
        $model->delete('invalid');
    }

    public function testUpdateAssignmentsNoActionRequired()
    {
        $this->_getModel()->updateAssignments(1, 3, false, false, false, false, false);

        $this->assertTablesEqual(
            $this->_loadDataSet()->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices', 'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices'
            )
        );
    }

    public function updateAssignmentsProvider()
    {
        return array(
            array('UpdateNoFilters', true, true, true, true, true),
            array('UpdateNonNotified', true, false, false, false, false),
            array('UpdateSuccess', false, true, false, false, false),
            array('UpdateNotified', false, false, true, false, false),
            array('UpdateError', false, false, false, true, false),
            array('UpdateGroups', false, false, false, false, true),
            array('UpdateCombined', true, true, false, true, false),
        );
    }

    /**
     * Test updateAssignments() with various filters
     * @dataProvider updateAssignmentsProvider
     */
    public function testUpdateAssignments(
        $datasetName,
        $deployNonnotified,
        $deploySuccess,
        $deployNotified,
        $deployError,
        $deployGroups
    )
    {
        $model = $this->_getModel(array('Library\Now' => new \DateTime('2015-02-08 14:17:29')));
        $model->updateAssignments(
            1415958319,
            3,
            $deployNonnotified,
            $deploySuccess,
            $deployNotified,
            $deployError,
            $deployGroups
        );

        $dataset = $this->_loadDataSet($datasetName);
        $this->assertTablesEqual(
            $dataset->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices', 'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices'
            )
        );
    }

    public function testUpdateAssignmentsException()
    {
        $this->setExpectedException('Model\Package\RuntimeException', 'database error');
        $data = array('Timestamp' => new \DateTime('@1415958319'));
        $clientConfig = $this->getMockBuilder('Database\Table\ClientConfig')->disableOriginalConstructor()->getMock();
        $clientConfig->method('getSql')->will($this->throwException(new \RuntimeException('database error')));
        $model = $this->_getModel(array('Database\Table\ClientConfig' => $clientConfig));
        $model->updateAssignments(1, 2, true, true, true, true, true);
    }
}
