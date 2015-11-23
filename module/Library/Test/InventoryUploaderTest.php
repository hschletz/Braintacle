<?php
/**
 * Tests for the InventoryUploader class
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

use \Library\InventoryUploader;
use \org\bovigo\vfs\vfsStream;

/**
 * Tests for the InventoryUploader class
 */
class InventoryUploaderTest extends \PHPUnit_Framework_TestCase
{
    public function testUploadFile()
    {
        $content = "testUploadFile\nline1\nline2\n";
        $root = vfsstream::setup('root');
        $url = vfsStream::newFile('test.txt')->withContent($content)->at($root)->url();
        $uploader = $this->getMockBuilder('Library\InventoryUploader')
                         ->disableOriginalConstructor()
                         ->setMethods(array('uploadData'))
                         ->getMock();
        $uploader->expects($this->once())
                 ->method('uploadData')
                 ->with($content)
                 ->will($this->returnValue('response'));
        $this->assertEquals('response', $uploader->uploadFile($url));
    }

    public function testUploadData()
    {
        $content = "testUploadFile\nline1\nline2\n";
        $adapter = $this->getMockBuilder('Zend\Http\Client\Adapter\Test')->setMethods(array('write'))->getMock();
        $adapter->expects($this->once())
                ->method('write')
                ->with(
                    'POST',
                    'http://example.net/server',
                    '1.1',
                    $this->callback(
                        function($headers) {
                            return (
                                $headers['User-Agent'] == 'Braintacle_local_upload' and
                                $headers['Content-Type'] == 'application/x-compress'
                            );
                        }
                    ),
                    $content
                );
        $uploader = new InventoryUploader('http://example.net/server', $adapter);
        $uploader->uploadData($content);

        // No public method available to test "strictredirects" option - use reflection
        $reflectionClass = new \ReflectionClass($adapter);
        $reflectionProperty = $reflectionClass->getProperty('config');
        $reflectionProperty->setAccessible(true);
        $this->assertTrue($reflectionProperty->getValue($adapter)['strictredirects']);
    }
}
