<?php

/**
 * Tests for Software hydrator
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

namespace Database\Test\Hydrator;

use Database\Hydrator\Software;

class SoftwareTest extends \PHPUnit\Framework\TestCase
{
    public function testHydrateWindows()
    {
        $hydrator = $this->createPartialMock(Software::class, ['hydrateValue']);
        $hydrator->method('hydrateValue')->will(
            $this->returnValueMap(
                array(
                    array('Name', '_name', '_Name'),
                    array('InstallLocation', '_install_location', '_InstallLocation'),
                    array('IsHotfix', '_is_hotfix', '_IsHotfix'),
                    array('InstallationDate', '_installation_date', '_InstallationDate'),
                    array('Architecture', '_architecture', '_Architecture'),
                )
            )
        );
        $agentData = array(
            'is_windows' => true,
            'is_android' => false,
            'name' => '_name',
            'version' => '_version',
            'comment' => '_comment',
            'publisher' => '_publisher',
            'install_location' => '_install_location',
            'is_hotfix' => '_is_hotfix',
            'guid' => '_guid',
            'language' => '_language',
            'installation_date' => '_installation_date',
            'architecture' => '_architecture',
        );
        $software = array(
            'Name' => '_Name',
            'Version' => '_version',
            'Comment' => '_comment',
            'Publisher' => '_publisher',
            'InstallLocation' => '_InstallLocation',
            'IsHotfix' => '_IsHotfix',
            'Guid' => '_guid',
            'Language' => '_language',
            'InstallationDate' => '_InstallationDate',
            'Architecture' => '_Architecture',
        );
        $object = new \ArrayObject();
        $this->assertSame($object, $hydrator->hydrate($agentData, $object));
        $this->assertEquals($software, $object->getArrayCopy());
    }

    public function testHydrateUnix()
    {
        $hydrator = $this->createPartialMock(Software::class, ['hydrateValue']);
        $hydrator->expects($this->never())->method('hydrateValue');
        $agentData = array(
            'is_windows' => false,
            'is_android' => false,
            'name' => '_name',
            'version' => '_version',
            'comment' => '_comment',
            'publisher' => 'ignored',
            'install_location' => 'ignored',
            'is_hotfix' => 'ignored',
            'guid' => 'ignored',
            'language' => 'ignored',
            'installation_date' => 'ignored',
            'architecture' => 'ignored',
            'size' => '_size',
        );
        $software = array(
            'Name' => '_name',
            'Version' => '_version',
            'Comment' => '_comment',
            'Size' => '_size',
        );
        $object = new \ArrayObject();
        $this->assertSame($object, $hydrator->hydrate($agentData, $object));
        $this->assertEquals($software, $object->getArrayCopy());
    }

    public function testHydrateAndroid()
    {
        $hydrator = $this->createPartialMock(Software::class, ['hydrateValue']);
        $hydrator->expects($this->never())->method('hydrateValue');
        $agentData = array(
            'is_windows' => false,
            'is_android' => true,
            'name' => '_name',
            'version' => '_version',
            'comment' => 'ignored',
            'publisher' => '_publisher',
            'install_location' => '_install_location',
            'is_hotfix' => 'ignored',
            'guid' => 'ignored',
            'language' => 'ignored',
            'installation_date' => 'ignored',
            'architecture' => 'ignored',
            'size' => 'ignored',
        );
        $software = array(
            'Name' => '_name',
            'Version' => '_version',
            'Publisher' => '_publisher',
            'InstallLocation' => '_install_location',
        );
        $object = new \ArrayObject();
        $this->assertSame($object, $hydrator->hydrate($agentData, $object));
        $this->assertEquals($software, $object->getArrayCopy());
    }

    public function testExtractWindows()
    {
        $hydrator = $this->createPartialMock(Software::class, ['extractValue']);
        $hydrator->method('extractValue')->will(
            $this->returnValueMap(
                array(
                    array('is_hotfix', '_IsHotfix', '_is_hotfix'),
                    array('installation_date', '_InstallationDate', '_installation_date'),
                )
            )
        );
        $software = (object) [
            'Name' => '_Name',
            'Version' => '_Version',
            'Comment' => '_Comment',
            'Publisher' => '_Publisher',
            'InstallLocation' => '_InstallLocation',
            'IsHotfix' => '_IsHotfix',
            'Guid' => '_Guid',
            'Language' => '_Language',
            'InstallationDate' => '_InstallationDate',
            'Architecture' => '_Architecture',
        ];
        $agentData = [
            'name' => '_Name',
            'version' => '_Version',
            'comment' => '_Comment',
            'publisher' => '_Publisher',
            'install_location' => '_InstallLocation',
            'is_hotfix' => '_is_hotfix',
            'guid' => '_Guid',
            'language' => '_Language',
            'installation_date' => '_installation_date',
            'architecture' => '_Architecture',
            'size' => null,
        ];
        $this->assertEquals($agentData, $hydrator->extract($software));
    }

    public function testExtractUnix()
    {
        $hydrator = $this->createPartialMock(Software::class, ['extractValue']);
        $hydrator->expects($this->never())->method('extractValue');
        $software = (object) [
            'Name' => '_Name',
            'Version' => '_Version',
            'Comment' => '_Comment',
            'Size' => '_Size',
        ];
        $agentData = [
            'name' => '_Name',
            'version' => '_Version',
            'comment' => '_Comment',
            'publisher' => null,
            'install_location' => null,
            'is_hotfix' => null,
            'guid' => null,
            'language' => null,
            'installation_date' => null,
            'architecture' => null,
            'size' => '_Size',
        ];
        $this->assertEquals($agentData, $hydrator->extract($software));
    }

    public function testExtractAndroid()
    {
        $hydrator = $this->createPartialMock(Software::class, ['extractValue']);
        $hydrator->expects($this->never())->method('extractValue');
        $software = (object) [
            'Name' => '_Name',
            'Version' => '_Version',
            'Publisher' => '_Publisher',
            'InstallLocation' => '_InstallLocation',
        ];
        $agentData = [
            'name' => '_Name',
            'version' => '_Version',
            'comment' => null,
            'publisher' => '_Publisher',
            'install_location' => '_InstallLocation',
            'is_hotfix' => null,
            'guid' => null,
            'language' => null,
            'installation_date' => null,
            'architecture' => null,
            'size' => null,
        ];
        $this->assertEquals($agentData, $hydrator->extract($software));
    }

    public function hydrateNameProvider()
    {
        return array(
            array('name', 'Name'),
            array('version', 'Version'),
            array('comment', 'Comment'),
            array('publisher', 'Publisher'),
            array('install_location', 'InstallLocation'),
            array('is_hotfix', 'IsHotfix'),
            array('guid', 'Guid'),
            array('language', 'Language'),
            array('installation_date', 'InstallationDate'),
            array('architecture', 'Architecture'),
            array('size', 'Size'),
        );
    }

    /**
     * @dataProvider hydrateNameProvider
     */
    public function testHydrateName($extracted, $hydrated)
    {
        $hydrator = new \Database\Hydrator\Software();
        $this->assertEquals($hydrated, $hydrator->hydrateName($extracted));
    }

    public function testHydrateNameInvalid()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Cannot hydrate name: invalid');
        $hydrator = new \Database\Hydrator\Software();
        $hydrator->hydrateName('invalid');
    }

    public function extractNameProvider()
    {
        return array(
            array('Name', 'name'),
            array('Version', 'version'),
            array('Comment', 'comment'),
            array('Publisher', 'publisher'),
            array('InstallLocation', 'install_location'),
            array('IsHotfix', 'is_hotfix'),
            array('Guid', 'guid'),
            array('Language', 'language'),
            array('InstallationDate', 'installation_date'),
            array('Architecture', 'architecture'),
            array('Size', 'size'),
        );
    }

    /**
     * @dataProvider extractNameProvider
     */
    public function testExtractName($hydrated, $extracted)
    {
        $hydrator = new \Database\Hydrator\Software();
        $this->assertEquals($extracted, $hydrator->extractName($hydrated));
    }

    public function testExtractNameInvalid()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Cannot extract name: Invalid');
        $hydrator = new \Database\Hydrator\Software();
        $hydrator->extractName('Invalid');
    }

    public function hydrateValueProvider()
    {
        return array(
            array('Name', "\xC2\x99", "\xE2\x84\xA2"),
            array('InstallLocation', 'N/A', null),
            array('InstallLocation', 'a/b', 'a\b'),
            array('IsHotfix', '0', true),
            array('IsHotfix', '1', false),
            array('InstallationDate', '2014-12-31', new \DateTime('2014-12-31')),
            array('InstallationDate', '', null),
            array('InstallationDate', null, null),
            array('Architecture', '64', '64'),
            array('Architecture', '32', '32'),
            array('Architecture', '0', null),
            array('Architecture', null, null),
            array('other', 'value', 'value'),
        );
    }

    /**
     * @dataProvider hydrateValueProvider
     */
    public function testHydrateValue($name, $extracted, $hydrated)
    {
        $hydrator = new \Database\Hydrator\Software();
        $this->assertEquals($hydrated, $hydrator->hydrateValue($name, $extracted));
    }

    public function extractValueProvider()
    {
        return array(
            array('is_hotfix', true, '0'),
            array('is_hotfix', false, '1'),
            array('installation_date', new \DateTime('2014-12-31'), '2014-12-31'),
            array('installation_date', '', null),
            array('installation_date', null, null),
            array('other', 'value', 'value'),
        );
    }

    /**
     * @dataProvider extractValueProvider
     */
    public function testExtractValue($name, $hydrated, $extracted)
    {
        $hydrator = new \Database\Hydrator\Software();
        $this->assertEquals($extracted, $hydrator->extractValue($name, $hydrated));
    }
}
