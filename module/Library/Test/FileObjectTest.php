<?php
/**
 * Tests for the FileObject class
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

namespace Library\Test;

use \Library\FileObject;
use \org\bovigo\vfs\vfsStream;

/**
 * Tests for the FileObject class
 */
class FileObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * vfsStream root container
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $_root;

    public function setUp()
    {
        $this->_root = vfsStream::setup('root');
    }

    public function testFileGetContentsSuccess()
    {
        $content = "testFileGetContentsSuccess\nline1\nline2\n";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $this->assertEquals($content, FileObject::fileGetContents($url));
    }

    public function testFileGetContentsEmptyFile()
    {
        $content = '';
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $this->assertEquals($content, FileObject::fileGetContents($url));
    }

    public function testFileGetContentsError()
    {
        $this->setExpectedException('RuntimeException', 'Error reading from file vfs://root/test.txt');
        // Force error by requesting nonexistent file
        FileObject::fileGetContents('vfs://root/test.txt');
    }

    public function testFileGetContentsAsArraySuccess()
    {
        $content = "line1\nline2\n";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $this->assertEquals(
            array("line1\n", "line2\n"),
            FileObject::fileGetContentsAsArray($url)
        );
    }

    public function testFileGetContentsAsArraySuccessWithFlags()
    {
        $content = "line1\nline2\n";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $this->assertEquals(
            array('line1', 'line2'),
            FileObject::fileGetContentsAsArray($url, \FILE_IGNORE_NEW_LINES)
        );
    }

    public function testFileGetContentsAsArrayEmptyFile()
    {
        $content = '';
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $this->assertEquals(array(), FileObject::fileGetContentsAsArray($url));
    }

    public function testFileGetContentsAsArrayError()
    {
        $this->setExpectedException('RuntimeException', 'Error reading from file vfs://root/test.txt');
        // Force error by requesting nonexistent file
        FileObject::fileGetContentsAsArray('vfs://root/test.txt');
    }

    public function testFilePutContentsSuccess()
    {
        $content = "line1\nline2\n";
        $filename = $this->_root->url() . '/test.txt';
        FileObject::filePutContents($filename, $content);
        $this->assertEquals($content, file_get_contents($filename));
    }

    public function testFilePutContentsOpenError()
    {
        $this->setExpectedException('RuntimeException', 'Error writing to file vfs://root/test.txt');
        // Force error by writing to write-protected file
        $filename = vfsStream::newFile('test.txt', 0000)->at($this->_root)->url();
        FileObject::filePutContents($filename, 'content');
    }

    public function testFilePutContentsWriteError()
    {
        // Force error by simulating full disk
        vfsStream::setQuota(3);
        $filename = $this->_root->url() . '/test.txt';
        try {
            FileObject::filePutContents($filename, 'content');
            $this->fail('Expected exception has not been thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals("Error writing to file $filename", $e->getMessage());
            // A truncated file should remain on disk
            $this->assertFileExists($filename);
            $this->assertEquals('con', file_get_contents($filename));
        }
    }
}
