<?php
/**
 * Tests for Model\Package\Storage\Direct
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Test\Package\Storage;

use Model\Package\Storage\Direct;
use Model\Package\Metadata;
use org\bovigo\vfs\vfsStream;

/**
 * Tests for Model\Package\Storage\Direct
 */
class DirectTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet;
    }

    public function testPrepare()
    {
        $data = array('Id' => 'id');
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('createDirectory'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->expects($this->once())->method('createDirectory')->with('id')->willReturn('path');
        $this->assertEquals('path', $model->prepare($data));
    }

    public function testWriteSuccess()
    {
        $data = array('foo' => 'bar');
        $file = 'file';
        $deleteSource = 'deleteSource';
        $numFragments = 'numFragments';
        $data2 = array('foo' => 'bar', 'NumFragments' => $numFragments);
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('writeContent', 'writeMetadata', 'cleanup'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->expects($this->once())
              ->method('writeContent')
              ->with($data, $file, $deleteSource)
              ->willReturn($numFragments);
        $model->expects($this->once())
              ->method('writeMetadata')
              ->with($data2);
        $model->expects($this->never())
              ->method('cleanup');
        $this->assertEquals($numFragments, $model->write($data, $file, $deleteSource));
    }

    public function testWriteErrorMetadata()
    {
        $data = array('Id' => 'id');
        $file = 'file';
        $deleteSource = 'deleteSource';
        $numFragments = 'numFragments';
        $data2 = $data + array('NumFragments' => $numFragments);
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('writeContent', 'writeMetadata', 'cleanup'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->expects($this->once())
              ->method('writeContent')
              ->with($data, $file, $deleteSource)
              ->willReturn($numFragments);
        $model->expects($this->once())
              ->method('writeMetadata')
              ->with($data2)
              ->will($this->throwException(new \RuntimeException('test')));
        $model->expects($this->once())
              ->method('cleanup')
              ->with('id');
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test');
        $model->write($data, $file, $deleteSource);
    }

    public function testWriteErrorContent()
    {
        $data = array('Id' => 'id');
        $file = 'file';
        $deleteSource = 'deleteSource';
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('writeContent', 'writeMetadata', 'cleanup'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->expects($this->once())
              ->method('writeContent')
              ->with($data, $file, $deleteSource)
              ->will($this->throwException(new \RuntimeException('test')));
        $model->expects($this->never())
              ->method('writeMetadata');
        $model->expects($this->once())
              ->method('cleanup')
              ->with('id');
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('test');
        $model->write($data, $file, $deleteSource);
    }

    public function testCleanupNoDir()
    {
        $root = vfsStream::setup('root');
        $path = $root->url() . '/path';
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->expects($this->once())->method('getPath')->with('id')->willReturn($path);
        $model->cleanup('id');
    }

    public function testCleanupRemoveFiles()
    {
        $root = vfsStream::setup('root');
        $dir = vfsStream::newDirectory('path')->at($root);
        $path = $dir->url();
        vfsStream::newFile('test')->at($dir);
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->method('getPath')->with('id')->willReturn($path);
        $model->cleanup('id');
        $this->assertFileDoesNotExist($path);
    }

    public function testCreateDirectorySuccess()
    {
        $root = vfsStream::setup('root');
        $path = $root->url() . '/path';
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->method('getPath')->with('id')->willReturn($path);
        $this->assertEquals($path, $model->createDirectory('id'));
        $this->assertTrue(is_dir($path));
    }

    public function testCreateDirectoryFailDirectoryExists()
    {
        $root = vfsStream::setup('root');
        $path = $root->url() . '/path';
        vfsStream::newDirectory('path')->at($root);
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->method('getPath')->with('id')->willReturn($path);

        $this->expectException('Model\Package\RuntimeException');
        $this->expectExceptionMessage('Package directory already exists: ' . $path);

        $model->createDirectory('id');
    }

    public function testCreateDirectoryFailDirectoryNotWritable()
    {
        $root = vfsStream::setup('root', 0000);
        $path = $root->url() . '/path';
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->method('getPath')->with('id')->willReturn($path);

        $this->expectException('Model\Package\RuntimeException');
        $this->expectExceptionMessage('Could not create package directory: ' . $path);

        $model->createDirectory('id');
        $this->assertFalse(is_dir($path));
    }

    public function testGetPath()
    {
        $config = $this->createMock('Model\Config');
        $config->method('__get')->with('packagePath')->willReturn('packagePath');
        $model = new Direct($config, new Metadata);
        $this->assertEquals('packagePath/id', $model->getPath('id'));
    }

    public function testWriteMetadata()
    {
        $data = array('Id' => 'id');

        $metadata = $this->createMock('Model\Package\Metadata');
        $metadata->expects($this->once())->method('setPackageData')->with($data);
        $metadata->expects($this->once())->method('save')->with('/path/info');

        $config = $this->createMock('Model\Config');
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->setConstructorArgs(array($config, $metadata))
                      ->getMock();
        $model->method('getPath')->with('id')->willReturn('/path');

        $model->writeMetadata($data);
    }

    public function testWriteMetadataValidate()
    {
        $data = array('Id' => 'id');

        $metadata = $this->createMock('Model\Package\Metadata');
        $metadata->expects($this->once())->method('setPackageData')->with($data, 'validate');

        $config = $this->createMock('Model\Config');
        $config->expects($this->once())->method('__get')->with('validateXml')->willReturn('validate');

        $model = $this->getMockBuilder($this->_getClass())
                      ->setConstructorArgs(array($config, $metadata))
                      ->setMethods(array('getPath'))
                      ->getMock();
        $model->writeMetadata($data);
    }

    public function testReadMetadata()
    {
        $metadata = $this->createMock('Model\Package\Metadata');
        $metadata->expects($this->once())->method('load')->with('/path/info');
        $metadata->expects($this->once())->method('getPackageData')->willReturn('packageData');

        $config = $this->createMock('Model\Config');
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->setConstructorArgs(array($config, $metadata))
                      ->getMock();
        $model->method('getPath')->with('id')->willReturn('/path');

        $this->assertEquals('packageData', $model->readMetadata('id'));
    }

    public function testWriteContentNoFile()
    {
        $data = array(
            'Id' => 'id',
            'FileLocation' => '',
        );
        $config = $this->createMock('Model\Config');
        $model = $this->_getModel(array('Model\Config' => $config));
        $this->assertSame(0, $model->writeContent($data, null, null));
    }

    public function writeContentProvider()
    {
        return [
            [0, 10, 1, true], // Empty file, ignore maxFragmentSize
            [0, 10, 1, false],
            [1025, 0, 1, true], // Split disabled
            [1025, 0, 1, false],
            [1025, null, 1, false], // NULL and 0 should be equivalent
            [1024, 1, 1, true], // Filesize <= maxFragmentSize (kB]
            [1024, 1, 1, false],
            [1025, 1, 2, false], // Split
            [1025, 1, 2, false],
            [2047, 1, 2, false],
            [2048, 1, 2, false],
            [2049, 1, 3, true],
        ];
    }

   /**
    * writeContent() test
    *
    * @param integer $fileSize File size
    * @param integer $maxFragmentSize Maximum fragment size
    * @param integer $expectedFragments Expected number of fragments
    * @param bool $deleteSource Delete source file?
    * @dataProvider writeContentProvider
    */
    public function testWriteContent($fileSize, $maxFragmentSize, $expectedFragments, $deleteSource)
    {
        $content = str_repeat('x', $fileSize);
        $root = vfsStream::setup('root');
        $sourceFile = vfsStream::newFile('test')->withContent($content)->at($root)->url();
        $packageDir = vfsStream::newDirectory('target')->at($root)->url();

        $data = array(
            'Id' => 'id',
            'FileLocation' => $sourceFile,
            'Size' => $fileSize,
            'MaxFragmentSize' => $maxFragmentSize,
        );

        $config = $this->createMock('Model\Config');
        $metadata = $this->createMock('Model\Package\Metadata');
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->setConstructorArgs(array($config, $metadata))
                      ->getMock();
        $model->method('getPath')->with('id')->willReturn($packageDir);

        $numFragments = $model->writeContent($data, $sourceFile, $deleteSource);
        $this->assertSame($expectedFragments, $numFragments);

        if ($deleteSource) {
            $this->assertFileDoesNotExist($sourceFile);
        } else {
            $this->assertFileExists($sourceFile);
        }

        $targetContent = '';
        for ($i = 1; $i <= $numFragments; $i++) {
            $targetFile = "$packageDir/id-$i";
            $this->assertFileExists($targetFile);
            if ($maxFragmentSize) {
                $this->assertLessThanOrEqual($maxFragmentSize * 1024, filesize($targetFile));
            }
            $targetContent .= file_get_contents($targetFile);
        }
        $this->assertFileDoesNotExist("$packageDir/id-" . ($numFragments + 1));
        $this->assertEquals($content, $targetContent);
    }
}
