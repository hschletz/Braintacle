<?php

/**
 * Tests for FileReadable validator
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

namespace Library\Test\Validator;

use Library\Validator\FileReadable;
use org\bovigo\vfs\vfsStream;

class FileReadableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * vfsStream root container
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $_root;

    public function setUp(): void
    {
        $this->_root = vfsStream::setup('root');
    }

    public function testFileReadable()
    {
        $url = vfsStream::newFile('test', 0444)->at($this->_root)->url();
        $validator = new FileReadable();
        $this->assertTrue($validator->isValid($url));
    }

    public function testFileNotReadable()
    {
        $url = vfsStream::newFile('test', 0000)->at($this->_root)->url();
        $validator = new FileReadable();
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(
            array(FileReadable::READABLE => "File '$url' is not readable"),
            $validator->getMessages()
        );
    }

    public function testDirectoryReadable()
    {
        $url = vfsStream::newDirectory('test', 0444)->at($this->_root)->url();
        $validator = new FileReadable();
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(FileReadable::FILE, key($validator->getMessages()));
    }

    public function testDirectoryNonReadable()
    {
        $url = vfsStream::newDirectory('test', 0000)->at($this->_root)->url();
        $validator = new FileReadable();
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(FileReadable::FILE, key($validator->getMessages()));
    }

    public function testNonExistent()
    {
        $url = $this->_root->url() . '/test';
        $validator = new FileReadable();
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(FileReadable::FILE, key($validator->getMessages()));
    }

    public function testEmpty()
    {
        $url = '';
        $validator = new FileReadable();
        $this->assertFalse($validator->isValid($url));
        $this->assertEquals(FileReadable::FILE, key($validator->getMessages()));
    }

    public function testFileMessage()
    {
        $url = $this->_root->url() . '/test';
        $validator = new FileReadable();
        $validator->isValid($url);
        $this->assertEquals(
            array(FileReadable::FILE => "'$url' is not a file or inaccessible"),
            $validator->getMessages()
        );
    }
}
