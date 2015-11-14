<?php
/**
 * Tests for the ArchiveManager class
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

namespace Library\Test;

use \Library\ArchiveManager;

class ArchiveManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @requires extension zip
     */
    public function testIsSupportedZip()
    {
        $manager = new ArchiveManager;
        $this->assertTrue($manager->isSupported(ArchiveManager::ZIP));
    }

    public function testIsSupportedInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported archive type: invalid');
        $manager = new ArchiveManager;
        $this->assertTrue($manager->isSupported('invalid'));
    }

    /**
     * @requires extension zip
     */
    public function testIsArchiveZipFalse()
    {
        $manager = new ArchiveManager;
        $this->assertFalse($manager->isArchive(ArchiveManager::ZIP, __FILE__));
    }

    public function testIsArchiveInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported archive type: invalid');
        $manager = new ArchiveManager;
        $this->assertFalse($manager->isArchive('invalid', __FILE__));
    }

    /**
     * @requires extension zip
     */
    public function testCreateArchiveZipError()
    {
        $this->setExpectedException('RuntimeException', "Error creating ZIP archive '', code ");
        $manager = new ArchiveManager;
        $manager->createArchive(ArchiveManager::ZIP, '');
    }

    public function testCreateArchiveInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported archive type: invalid');
        $manager = new ArchiveManager;
        $manager->createArchive('invalid', __FILE__);
    }

    /**
     * @requires extension zip
     */
    public function testCloseArchiveZipError()
    {
        $this->setExpectedException('RuntimeException', 'Error closing ZIP archive');
        $manager = new ArchiveManager;
        $manager->closeArchive(new \ZipArchive);
    }

    /**
     * @requires extension zip
     */
    public function testCloseArchiveZipErrorIgnore()
    {
        $archive = $this->getMock('ZipArchive');
        $archive->expects($this->once())->method('close');
        $manager = new ArchiveManager;
        $manager->closeArchive($archive, true);
    }

    public function testCloseArchiveInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported archive');
        $manager = new ArchiveManager;
        $manager->closeArchive(null);
    }

    /**
     * @requires extension zip
     */
    public function testAddFileZipError()
    {
        $this->setExpectedException('RuntimeException', "Error adding file 'file' to archive as 'name'");
        $manager = new ArchiveManager;
        $manager->addFile(new \ZipArchive, 'file', 'name');
    }

    public function testAddFileInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported archive');
        $manager = new ArchiveManager;
        $manager->addFile(null, 'file', 'name');
    }

    /**
     * @requires extension zip
     */
    public function testZipArchiveCreation()
    {
        // Zip extension does not support stream wrappers. Use real filesystem objects.
        $tmpFile = tmpfile();
        $archiveFile = stream_get_meta_data($tmpFile)['uri'];

        $manager = new ArchiveManager;
        $archive = $manager->createArchive(ArchiveManager::ZIP, $archiveFile);
        $manager->addFile($archive, __FILE__, 'äöü.txt');
        $manager->closeArchive($archive);

        $this->assertFileExists($archiveFile);
        $this->assertTrue($manager->isArchive(ArchiveManager::ZIP, $archiveFile));

        $testArchive = new \ZipArchive;
        $this->assertTrue($testArchive->open($archiveFile));
        $this->assertEquals(1, $testArchive->numFiles);
        $content = $testArchive->getFromName('äöü.txt');
        $this->assertNotFalse($content); // Message is easier readable in case of error
        $this->assertEquals(file_get_contents(__FILE__), $content);
    }
}
