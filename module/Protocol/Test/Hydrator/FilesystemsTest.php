<?php

/**
 * Tests for Filesystems hydrator
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

namespace Protocol\Test\Hydrator;

class FilesystemsTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    public function hydrateProvider()
    {
        $windowsAgent = array(
            'LETTER' => 'C:/',
            'TYPE' => '_type',
            'FILESYSTEM' => '_filesystem',
            'TOTAL' => '3',
            'FREE' => '2',
            'VOLUMN' => '_label',
            'CREATEDATE' => null,
        );
        $unixAgent = array(
            'LETTER' => null,
            'TYPE' => '_mountpoint',
            'FILESYSTEM' => '_filesystem',
            'TOTAL' => '3',
            'FREE' => '2',
            'VOLUMN' => '_device',
            'CREATEDATE' => '2015/5/4 21:34:45',
        );
        $windowsFilesystem = array(
            'Letter' => 'C:',
            'Type' => '_type',
            'Label' => '_label',
            'Filesystem' => '_filesystem',
            'Size' => 3,
            'FreeSpace' => 2,
            'UsedSpace' => 1,
        );
        $unixFilesystem = array(
            'Mountpoint' => '_mountpoint',
            'Device' => '_device',
            'CreationDate' => new \DateTime('2015-05-04T21:34:45'),
            'Filesystem' => '_filesystem',
            'Size' => 3,
            'FreeSpace' => 2,
            'UsedSpace' => 1,
        );
        return array(
            array($windowsAgent, $windowsFilesystem),
            array($unixAgent, $unixFilesystem),
        );
    }

    public function extractProvider()
    {
        $windowsFilesystem = array(
            'Letter' => 'C:',
            'Type' => '_type',
            'Label' => '_label',
            'Filesystem' => '_filesystem',
            'Size' => 3,
            'FreeSpace' => 2,
            'UsedSpace' => 1,
        );
        $unixFilesystem = array(
            'Mountpoint' => '_mountpoint',
            'Device' => '_device',
            'CreationDate' => new \DateTime('2015-05-04'),
            'Filesystem' => '_filesystem',
            'Size' => 3,
            'FreeSpace' => 2,
            'UsedSpace' => 1,
        );
        $windowsAgent = array(
            'LETTER' => 'C:',
            'TYPE' => '_type',
            'FILESYSTEM' => '_filesystem',
            'TOTAL' => '3',
            'FREE' => '2',
            'VOLUMN' => '_label',
            'CREATEDATE' => null,
        );
        $unixAgent = array(
            'LETTER' => null,
            'TYPE' => '_mountpoint',
            'FILESYSTEM' => '_filesystem',
            'TOTAL' => '3',
            'FREE' => '2',
            'VOLUMN' => '_device',
            'CREATEDATE' => '2015/5/4 00:00:00',
        );
        return array(
            array($windowsFilesystem, $windowsAgent),
            array($unixFilesystem, $unixAgent),
        );
    }

    public function hydrateNameProvider()
    {
        return array(
            array('LETTER', 'Letter'),
            array('CREATEDATE', 'CreationDate'),
            array('FILESYSTEM', 'Filesystem'),
            array('TOTAL', 'Size'),
            array('FREE', 'FreeSpace'),
        );
    }

    /**
     * @dataProvider hydrateNameProvider
     */
    public function testHydrateName($extracted, $hydrated)
    {
        $hydrator = $this->getHydrator();
        $this->assertEquals($hydrated, $hydrator->hydrateName($extracted));
    }

    public function testHydrateNameInvalid()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Cannot hydrate name: invalid');
        $hydrator = $this->getHydrator();
        $hydrator->hydrateName('invalid');
    }

    public function extractNameProvider()
    {
        return array(
            array('Letter', 'LETTER'),
            array('Type', 'TYPE'),
            array('Label', 'VOLUMN'),
            array('Mountpoint', 'TYPE'),
            array('Device', 'VOLUMN'),
            array('CreationDate', 'CREATEDATE'),
            array('Filesystem', 'FILESYSTEM'),
            array('Size', 'TOTAL'),
            array('FreeSpace', 'FREE'),
        );
    }

    /**
     * @dataProvider extractNameProvider
     */
    public function testExtractName($hydrated, $extracted)
    {
        $hydrator = $this->getHydrator();
        $this->assertEquals($extracted, $hydrator->extractName($hydrated));
    }

    public function testExtractNameInvalid()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Cannot extract name: Invalid');
        $hydrator = $this->getHydrator();
        $hydrator->extractName('Invalid');
    }

    public function hydrateValueProvider()
    {
        return array(
            array('Letter', 'C:/', 'C:'),
            array('Letter', 'C:', 'C:'),
            array('CreationDate', '2015/5/4 21:34:45', new \DateTime('2015-05-04T21:34:45')),
            array('CreationDate', '', null),
            array('CreationDate', null, null),
            array('Filesystem', '_filesystem', '_filesystem'),
        );
    }

    /**
     * @dataProvider hydrateValueProvider
     */
    public function testHydrateValue($name, $extracted, $hydrated)
    {
        $hydrator = $this->getHydrator();
        $this->assertEquals($hydrated, $hydrator->hydrateValue($name, $extracted));
    }

    public function extractValueProvider()
    {
        return array(
            array('LETTER', 'C:', 'C:'),
            array('createdate', new \DateTime('2015-05-04'), '2015/5/4 00:00:00'),
            array('CREATEDATE', new \DateTime('2015-05-04'), '2015/5/4 00:00:00'),
            array('CREATEDATE', '', null),
            array('CREATEDATE', null, null),
            array('FILESYSTEM', '_filesystem', '_filesystem'),
        );
    }

    /**
     * @dataProvider extractValueProvider
     */
    public function testExtractValue($name, $hydrated, $extracted)
    {
        $hydrator = $this->getHydrator();
        $this->assertEquals($extracted, $hydrator->extractValue($name, $hydrated));
    }
}
