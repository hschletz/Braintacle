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

namespace Database\Test\Hydrator;

class FilesystemsTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    public function hydrateProvider()
    {
        $windowsAgent = array(
            'letter' => 'C:/',
            'type' => '_type',
            'filesystem' => '_filesystem',
            'total' => '3',
            'free' => '2',
            'volumn' => '_label',
            'createdate' => null,
        );
        $unixAgent = array(
            'letter' => null,
            'type' => '_mountpoint',
            'filesystem' => '_filesystem',
            'total' => '3',
            'free' => '2',
            'volumn' => '_device',
            'createdate' => '2014-12-31',
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
            'CreationDate' => new \DateTime('2014-12-31'),
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
            'CreationDate' => new \DateTime('2014-12-31'),
            'Filesystem' => '_filesystem',
            'Size' => 3,
            'FreeSpace' => 2,
            'UsedSpace' => 1,
        );
        $windowsAgent = array(
            'letter' => 'C:',
            'type' => '_type',
            'filesystem' => '_filesystem',
            'total' => '3',
            'free' => '2',
            'volumn' => '_label',
            'createdate' => null,
        );
        $unixAgent = array(
            'letter' => null,
            'type' => '_mountpoint',
            'filesystem' => '_filesystem',
            'total' => '3',
            'free' => '2',
            'volumn' => '_device',
            'createdate' => '2014-12-31',
        );
        return array(
            array($windowsFilesystem, $windowsAgent),
            array($unixFilesystem, $unixAgent),
        );
    }

    public function hydrateNameProvider()
    {
        return array(
            array('letter', 'Letter'),
            array('createdate', 'CreationDate'),
            array('filesystem', 'Filesystem'),
            array('total', 'Size'),
            array('free', 'FreeSpace'),
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
            array('Letter', 'letter'),
            array('Type', 'type'),
            array('Label', 'volumn'),
            array('Mountpoint', 'type'),
            array('Device', 'volumn'),
            array('CreationDate', 'createdate'),
            array('Filesystem', 'filesystem'),
            array('Size', 'total'),
            array('FreeSpace', 'free'),
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
            array('CreationDate', '2014-12-31', new \DateTime('2014-12-31')),
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
            array('letter', 'C:', 'C:'),
            array('createdate', new \DateTime('2014-12-31'), '2014-12-31'),
            array('createdate', '', null),
            array('createdate', null, null),
            array('filesystem', '_filesystem', '_filesystem'),
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
