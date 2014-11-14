<?php
/**
 * Tests for Model\Package\Storage\Direct
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

namespace Model\Test\Package\Storage;
use Model\Package\Storage\Direct;
use Model\Package\Metadata;

/**
 * Tests for Model\Package\Storage\Direct
 */
class DirectTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet;
    }

    public function testGetPath()
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('__get')->with('packagePath')->willReturn('packagePath');
        $model = new Direct($config, new Metadata);
        $timestamp = new \Zend_Date(1415610660, \Zend_Date::TIMESTAMP);
        $this->assertEquals('packagePath/1415610660', $model->getPath($timestamp));
    }

    public function testWriteMetadata()
    {
        $timestamp = new \Zend_Date(1415610660, \Zend_Date::TIMESTAMP);
        $data = array('Timestamp' => $timestamp);

        $metadata = $this->getMock('Model\Package\Metadata');
        $metadata->expects($this->once())->method('setPackageData')->with($data);
        $metadata->expects($this->once())->method('save')->with('/path/info');

        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $model = $this->getMockBuilder('Model\Package\Storage\Direct')
                      ->setMethods(array('getPath'))
                      ->setConstructorArgs(array($config, $metadata))
                      ->getMock();
        $model->method('getPath')->with($timestamp)->willReturn('/path');

        $model->writeMetadata($data);
    }
}
