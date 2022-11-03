<?php

/**
 * Tests for Software hydrator
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

use Database\Hydrator\Software;
use DateTime;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Model\Client\Item\Software as ItemSoftware;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class SoftwareTest extends \PHPUnit\Framework\TestCase
{
    public function hydrateProvider()
    {
        $hydratedWindows = [
            'name' => 'NameHydrated',
            'version' => 'Version',
            'comment' => 'Comment',
            'publisher' => 'Publisher',
            'installLocation' => 'InstallLocationHydrated',
            'isHotfix' => 'IsHotfixHydrated',
            'guid' => 'Guid',
            'language' => 'Language',
            'installationDate' => new DateTime('2022-11-03'),
            'architecture' => 64,
        ];
        $extractedWindows = [
            'is_windows' => true,
            'is_android' => false,
            'name' => 'NameExtracted',
            'version' => 'Version',
            'comment' => 'Comment',
            'publisher' => 'Publisher',
            'install_location' => 'InstallLocationExtracted',
            'is_hotfix' => 'IsHotfixExtracted',
            'guid' => 'Guid',
            'language' => 'Language',
            'installation_date' => 'InstallationDateExtracted',
            'architecture' => 'ArchitectureExtracted',
        ];

        $hydratedUnix = [
            'name' => 'Name',
            'version' => 'Version',
            'comment' => 'Comment',
            'size' => 42,
        ];
        $extractedUnix = [
            'is_windows' => false,
            'is_android' => false,
            'name' => 'Name',
            'version' => 'Version',
            'comment' => 'Comment',
            'publisher' => 'ignored',
            'install_location' => 'ignored',
            'is_hotfix' => 'ignored',
            'guid' => 'ignored',
            'language' => 'ignored',
            'installation_date' => 'ignored',
            'architecture' => 'ignored',
            'size' => 42,
        ];

        $hydratedAndroid = [
            'name' => 'Name',
            'version' => 'Version',
            'publisher' => 'Publisher',
            'installLocation' => 'InstallLocation',
        ];
        $extractedAndroid = [
            'is_windows' => false,
            'is_android' => true,
            'name' => 'Name',
            'version' => 'Version',
            'comment' => 'ignored',
            'publisher' => 'Publisher',
            'install_location' => 'InstallLocation',
            'is_hotfix' => 'ignored',
            'guid' => 'ignored',
            'language' => 'ignored',
            'installation_date' => 'ignored',
            'architecture' => 'ignored',
            'size' => 'ignored',
        ];

        return [
            [$hydratedWindows, $extractedWindows],
            [$hydratedUnix, $extractedUnix],
            [$hydratedAndroid, $extractedAndroid],
        ];
    }

    /** @dataProvider hydrateProvider */
    public function testHydrate($hydrated, $extracted)
    {
        /** @var MockObject|Software */
        $hydrator = $this->createPartialMock(Software::class, ['hydrateValue']);
        $hydrator->method('hydrateValue')->willReturnMap([
            ['name', 'NameExtracted', 'NameHydrated'],
            ['installLocation', 'InstallLocationExtracted', 'InstallLocationHydrated'],
            ['isHotfix', 'IsHotfixExtracted', 'IsHotfixHydrated'],
            ['installationDate', 'InstallationDateExtracted', new DateTime('2022-11-03')],
            ['architecture', 'ArchitectureExtracted', 64],
        ]);

        $object = new ItemSoftware();
        $this->assertSame($object, $hydrator->hydrate($extracted, $object));
        $this->assertEquals($hydrated, get_object_vars($object));
    }

    public function extractProvider()
    {
        $hydratedWindows = [
            'name' => 'Name',
            'version' => 'Version',
            'comment' => 'Comment',
            'publisher' => 'Publisher',
            'installLocation' => 'InstallLocation',
            'isHotfix' => true,
            'guid' => 'Guid',
            'language' => 'Language',
            'installationDate' => new DateTime('2022-11-03'),
            'architecture' => 64,
        ];
        $extractedWindows = [
            'name' => 'Name',
            'version' => 'Version',
            'comment' => 'Comment',
            'publisher' => 'Publisher',
            'install_location' => 'InstallLocation',
            'is_hotfix' => 'IsHotfixExtracted',
            'guid' => 'Guid',
            'language' => 'Language',
            'installation_date' => 'InstallationDateExtracted',
            'architecture' => 64,
            'size' => null,
        ];

        $hydratedUnix = [
            'name' => 'Name',
            'version' => 'Version',
            'comment' => 'Comment',
            'size' => 42,
        ];
        $extractedUnix = [
            'name' => 'Name',
            'version' => 'Version',
            'comment' => 'Comment',
            'publisher' => null,
            'install_location' => null,
            'is_hotfix' => null,
            'guid' => null,
            'language' => null,
            'installation_date' => null,
            'architecture' => null,
            'size' => 42,
        ];

        $hydratedAndroid = [
            'name' => 'Name',
            'version' => 'Version',
            'publisher' => 'Publisher',
            'installLocation' => 'InstallLocation',
        ];
        $extractedAndroid = [
            'name' => 'Name',
            'version' => 'Version',
            'comment' => null,
            'publisher' => 'Publisher',
            'install_location' => 'InstallLocation',
            'is_hotfix' => null,
            'guid' => null,
            'language' => null,
            'installation_date' => null,
            'architecture' => null,
            'size' => null,
        ];

        return [
            [$hydratedWindows, $extractedWindows],
            [$hydratedUnix, $extractedUnix],
            [$hydratedAndroid, $extractedAndroid],
        ];
    }

    /** @dataProvider extractProvider */
    public function testExtract($hydrated, $extracted)
    {
        /** @var MockObject|Software */
        $hydrator = $this->createPartialMock(Software::class, ['extractValue']);
        $hydrator->method('extractValue')->willReturnCallback(function ($name, $value) {
            if ($name == 'is_hotfix' && $value === true) {
                return 'IsHotfixExtracted';
            }
            if ($name == 'installation_date' && $value == new DateTime('2022-11-03')) {
                return 'InstallationDateExtracted';
            }
        });

        $object = (new ObjectPropertyHydrator())->hydrate($hydrated, new ItemSoftware());
        $this->assertEquals($extracted, $hydrator->extract($object));
    }

    public function hydrateNameProvider()
    {
        return [
            ['name', 'name'],
            ['version', 'version'],
            ['comment', 'comment'],
            ['publisher', 'publisher'],
            ['install_location', 'installLocation'],
            ['is_hotfix', 'isHotfix'],
            ['guid', 'guid'],
            ['language', 'language'],
            ['installation_date', 'installationDate'],
            ['architecture', 'architecture'],
            ['size', 'size'],
        ];
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
        return [
            ['name', 'name'],
            ['version', 'version'],
            ['comment', 'comment'],
            ['publisher', 'publisher'],
            ['installLocation', 'install_location'],
            ['isHotfix', 'is_hotfix'],
            ['guid', 'guid'],
            ['language', 'language'],
            ['installationDate', 'installation_date'],
            ['architecture', 'architecture'],
            ['size', 'size'],
        ];
    }

    /**
     * @dataProvider extractNameProvider
     */
    public function testExtractName($hydrated, $extracted)
    {
        $hydrator = new \Database\Hydrator\Software();
        $this->assertEquals($extracted, $hydrator->extractName($hydrated));
    }

    public function testExtractNameWithLegacyUpperCaseName()
    {
        $hydrator = new Software();
        $this->assertEquals('is_hotfix', $hydrator->extractName('IsHotfix'));
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
        return [
            ['name', "\xC2\x99", "\xE2\x84\xA2"],
            ['installLocation', 'N/A', null],
            ['installLocation', 'a/b', 'a\b'],
            ['isHotfix', '0', true],
            ['isHotfix', '1', false],
            ['installationDate', '2014-12-31', new DateTime('2014-12-31')],
            ['installationDate', '', null],
            ['installationDate', null, null],
            ['architecture', '64', '64'],
            ['architecture', '32', '32'],
            ['architecture', '0', null],
            ['architecture', null, null],
            ['other', 'value', 'value'],
        ];
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
