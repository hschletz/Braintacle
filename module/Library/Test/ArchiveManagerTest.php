<?php

/**
 * Tests for the ArchiveManager class
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

namespace Library\Test;

use Library\ArchiveManager;
use PHPUnit\Framework\MockObject\MockObject;
use ZipArchive;

class ArchiveManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @requires extension zip
     */
    public function testIsSupportedZip()
    {
        $manager = new ArchiveManager();
        $this->assertTrue($manager->isSupported(ArchiveManager::ZIP));
    }

    public function testIsSupportedInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unsupported archive type: invalid');
        $manager = new ArchiveManager();
        $this->assertTrue($manager->isSupported('invalid'));
    }

    /**
     * @requires extension zip
     */
    public function testIsArchiveZipFalse()
    {
        $manager = new ArchiveManager();
        $this->assertFalse($manager->isArchive(ArchiveManager::ZIP, __FILE__));
    }

    public function testIsArchiveInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unsupported archive type: invalid');
        $manager = new ArchiveManager();
        $this->assertFalse($manager->isArchive('invalid', __FILE__));
    }

    /**
     * @requires extension zip
     */
    public function testCreateArchiveZipError()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage("Error creating ZIP archive '', code ");
        $manager = new ArchiveManager();
        @$manager->createArchive(ArchiveManager::ZIP, '');
    }

    public function testCreateArchiveInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unsupported archive type: invalid');
        $manager = new ArchiveManager();
        $manager->createArchive('invalid', '');
    }

    public function testCreateArchiveFileExists()
    {
        $tmpFile = tmpfile();
        $filename = stream_get_meta_data($tmpFile)['uri'];
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Archive already exists: ' . $filename);
        $manager = new ArchiveManager();
        $manager->createArchive('something', $filename);
    }

    /**
     * @requires extension zip
     */
    public function testCloseArchiveZipError()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Error closing ZIP archive');
        $manager = new ArchiveManager();
        $manager->closeArchive(new \ZipArchive());
    }

    /**
     * @requires extension zip
     */
    public function testCloseArchiveZipErrorIgnore()
    {
        /** @var MockObject|ZipArchive */
        $archive = $this->createMock('ZipArchive');
        $archive->expects($this->once())->method('close');
        $manager = new ArchiveManager();
        $manager->closeArchive($archive, true);
    }

    /**
     * @requires extension zip
     */
    public function testAddFileZipError()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage("Error adding file 'file' to archive as 'name'");
        $manager = new ArchiveManager();
        $manager->addFile(new \ZipArchive(), 'file', 'name');
    }

    /**
     * @requires extension zip
     */
    public function testZipArchiveCreation()
    {
        // The Zip extension does not support stream wrappers. Use real
        // filesystem objects instead. Since the target file must not exist,
        // tmpfile() is not suitable. Instead, use tempnam() with a dedicated
        // directory to get a safe filename and delete the created file. This
        // is mostly safe because the only source for filename clashes would be
        // another test running on the same tree in parallel, and the randomized
        // filename part reduces the risk even further.
        $tmpDir = \Library\Module::getPath('data/Test/ArchiveManager');
        $archiveFile = tempnam($tmpDir, 'zip');

        try {
            if (dirname($archiveFile) != $tmpDir) {
                throw new \UnexpectedValueException('Could not generate temporary file in safe location');
            }

            unlink($archiveFile);
            $manager = new ArchiveManager();
            $archive = $manager->createArchive(ArchiveManager::ZIP, $archiveFile);
            $manager->addFile($archive, __FILE__, 'äöü.txt');
            $manager->closeArchive($archive);

            $this->assertFileExists($archiveFile);
            $this->assertTrue($manager->isArchive(ArchiveManager::ZIP, $archiveFile));

            $testArchive = new \ZipArchive();
            $this->assertTrue($testArchive->open($archiveFile));
            $this->assertEquals(1, $testArchive->numFiles);
            $content = $testArchive->getFromName('äöü.txt');
            $testArchive->close();
            $this->assertNotFalse($content); // Message is easier readable in case of error
            $this->assertEquals(file_get_contents(__FILE__), $content);

            unlink($archiveFile);
        } catch (\Exception $e) {
            if ($archiveFile) {
                @unlink($archiveFile);
                throw $e;
            }
        }
    }
}
