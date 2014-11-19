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

    public function testOpenAndClose()
    {
        $url = $this->_root->url() . '/test.txt';
        $fileObject = new FileObject($url, 'w');
        $this->assertFileExists($url);
        $this->assertEquals($url, $fileObject->getPathname()); // Test parent constructor invocation

        $reflectionObject = new \ReflectionClass($fileObject);
        $reflectionProperty = $reflectionObject->getProperty('_file');
        $reflectionProperty->setAccessible(true);
        $file = $reflectionProperty->getValue($fileObject);

        $metadata = stream_get_meta_data($file);
        $this->assertEquals($url, $metadata['uri']);
        $this->assertEquals('w', $metadata['mode']);

        // Close file by destroying the object. File pointer should become invalid.
        unset($fileObject);
        $this->assertFalse(@stream_get_meta_data($file));
    }

    public function testOpenError()
    {
        $url = $this->_root->url() . '/test.txt';
        $this->setExpectedException('RuntimeException', "Error opening file '$url', mode 'r'");
        $fileObject = new FileObject($url); // default mode 'r'
    }

    public function testSetFlags()
    {
        $url = $this->_root->url() . '/test.txt';
        $fileObject = new FileObject($url, 'w');
        $fileObject->setFlags('test_flags');

        $reflectionObject = new \ReflectionClass($fileObject);
        $reflectionProperty = $reflectionObject->getProperty('_flags');
        $reflectionProperty->setAccessible(true);
        $this->assertEquals('test_flags', $reflectionProperty->getValue($fileObject));
    }

    public function testSetFlagsUnimplementedFlag()
    {
        $this->setExpectedException('LogicException', 'READ_CSV not implemented');
        $url = $this->_root->url() . '/test.txt';
        $fileObject = new FileObject($url, 'w');
        $fileObject->setFlags(\SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_CSV);
    }

    public function testEofTrue()
    {
        $url = vfsStream::newFile('test.txt')->at($this->_root)->url();
        $fileObject = new FileObject($url, 'r');
        $this->assertTrue($fileObject->eof());
    }

    public function testEofFalse()
    {
        $url = vfsStream::newFile('test.txt')->withContent('test')->at($this->_root)->url();
        $fileObject = new FileObject($url, 'r');
        $this->assertFalse($fileObject->eof());
    }

    public function testFgetsRaw()
    {
        $content = "line1\nline2\r\nline3";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $fileObject = new FileObject($url, 'r');
        $this->assertEquals("line1\n", $fileObject->fgets());
        $this->assertEquals("line2\r\n", $fileObject->fgets());
        $this->assertEquals("line3", $fileObject->fgets());
        try {
            $failMessage = 'fgets() beyond EOF should have thrown exception';
            $fileObject->fgets();
            $this->fail($failMessage);
        } catch (\RuntimeException $e) {
            $this->assertNotEquals($failMessage, $e->getMessage());
        }
    }

    public function testFgetsDropNewLine()
    {
        $content = "line1\nline2\r\nline3";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $fileObject = new FileObject($url, 'r');
        $fileObject->setFlags(\SplFileObject::DROP_NEW_LINE);
        $this->assertEquals("line1", $fileObject->fgets());
        $this->assertEquals("line2", $fileObject->fgets());
        $this->assertEquals("line3", $fileObject->fgets());
        try {
            $failMessage = 'fgets() beyond EOF should have thrown exception';
            $fileObject->fgets();
            $this->fail($failMessage);
        } catch (\RuntimeException $e) {
            $this->assertNotEquals($failMessage, $e->getMessage());
        }
    }

    public function testFgetsReadError()
    {
        $this->setExpectedException('RuntimeException', 'Error reading from file fail:');
        $fileObject = new FileObject('fail://', 'r');
        @$fileObject->fgets();
    }

    public function testNextReadError()
    {
        $this->setExpectedException('RuntimeException', 'Error reading from file fail:');
        $fileObject = new FileObject('fail://', 'r');
        @$fileObject->next();
    }

    public function testRewindError()
    {
        $this->setExpectedException('RuntimeException', 'Error rewinding file fail:');
        $fileObject = new FileObject('fail://', 'r');
        @$fileObject->rewind();
    }

    public function testIteratorInterfaceEmptyFile()
    {
        $url = vfsStream::newFile('test.txt')->at($this->_root)->url();
        $fileObject = new FileObject($url, 'r');
        $fileObject->rewind();

        $this->assertFalse($fileObject->valid());
        $this->assertFalse($fileObject->current());
        $this->assertSame(0, $fileObject->key());
    }

    public function testIteratorInterfaceRaw()
    {
        $content = "line1\nline2";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $fileObject = new FileObject($url, 'r');
        $fileObject->rewind();

        $this->assertTrue($fileObject->valid());
        $this->assertEquals("line1\n", $fileObject->current());
        $this->assertSame(0, $fileObject->key());

        $fileObject->next();
        $this->assertTrue($fileObject->valid());
        $this->assertEquals('line2', $fileObject->current());
        $this->assertSame(1, $fileObject->key());

        $fileObject->next(); // Beyond EOF
        $this->assertFalse($fileObject->valid());
        $this->assertFalse($fileObject->current());
        $this->assertSame(2, $fileObject->key());

        $fileObject->rewind();
        $this->assertTrue($fileObject->valid());
        $this->assertEquals("line1\n", $fileObject->current());
        $this->assertSame(0, $fileObject->key());
    }

    public function testIteratorInterfaceSkipEmpty()
    {
        $content = "line1\n\nline2";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $fileObject = new FileObject($url, 'r');
        $fileObject->setFlags(\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);
        $fileObject->rewind();

        $this->assertTrue($fileObject->valid());
        $this->assertEquals("line1", $fileObject->current());
        $this->assertSame(0, $fileObject->key());

        $fileObject->next();
        $this->assertTrue($fileObject->valid());
        $this->assertEquals('line2', $fileObject->current());
        $this->assertSame(1, $fileObject->key());

        $fileObject->next(); // Beyond EOF
        $this->assertFalse($fileObject->valid());
        $this->assertFalse($fileObject->current());
        $this->assertSame(2, $fileObject->key());

        $fileObject->rewind();
        $this->assertTrue($fileObject->valid());
        $this->assertEquals("line1", $fileObject->current());
        $this->assertSame(0, $fileObject->key());
    }

    public function testIteratorEmptyFile()
    {
        $url = vfsStream::newFile('test.txt')->at($this->_root)->url();
        $fileObject = new FileObject($url);
        $content = array();
        foreach ($fileObject as $line) {
            $content[] = $line;
        }
        $this->assertEquals(array(), $content);
    }

    public function testIteratorRaw()
    {
        $expectedContent = array("\n", "line1\n", "line2\n", 'line3');
        $url = vfsStream::newFile('test.txt')->withContent(implode('', $expectedContent))->at($this->_root)->url();
        $fileObject = new FileObject($url);
        $content = array();
        foreach ($fileObject as $line) {
            $content[] = $line;
        }
        $this->assertEquals($expectedContent, $content);
    }

    public function testIteratorDropNewLine()
    {
        $expectedContent = array("", "line1", "line2", 'line3');
        $url = vfsStream::newFile('test.txt')->withContent(implode("\n", $expectedContent))->at($this->_root)->url();
        $fileObject = new FileObject($url);
        $fileObject->setFlags(\SplFileObject::DROP_NEW_LINE);
        $content = array();
        foreach ($fileObject as $line) {
            $content[] = $line;
        }
        $this->assertEquals($expectedContent, $content);
    }

    public function testIteratorSkipEmpty()
    {
        $content = "\nline1\n\nline2\nline3";
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($this->_root)->url();
        $fileObject = new FileObject($url);
        $fileObject->setFlags(\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);
        $content = array();
        foreach ($fileObject as $lineNo => $line) {
            $content[$lineNo] = $line;
        }
        $expectedContent = array("line1", "line2", "line3");
        $this->assertEquals($expectedContent, $content);
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
