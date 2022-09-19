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

namespace Protocol\Test\Hydrator;

class SoftwareTest extends \PHPUnit\Framework\TestCase
{
    public function testHydrate()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('not implemented');

        $databaseHydrator = $this->createMock(\Database\Hydrator\Software::class);
        $hydrator = new \Protocol\Hydrator\Software($databaseHydrator);
        $hydrator->hydrate([], new \stdClass());
    }

    public function extractProvider()
    {
        return [
            ['2020-12-05', '2020/12/05'],
            [null, null],
        ];
    }

    /** @dataProvider extractProvider */
    public function testExtract($installationDateDatabase, $installationDateProtocol)
    {
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
        $databaseContent = [
            'NAME' => '_Name',
            'VERSION' => '_Version',
            'COMMENT' => '_Comment',
            'PUBLISHER' => '_Publisher',
            'INSTALL_LOCATION' => '_InstallLocation',
            'IS_HOTFIX' => '_source',
            'GUID' => '_Guid',
            'LANGUAGE' => '_Language',
            'INSTALLATION_DATE' => $installationDateDatabase,
            'ARCHITECTURE' => '_Architecture',
            'SIZE' => '_Size',
        ];
        $agentData = [
            'NAME' => '_Name',
            'VERSION' => '_Version',
            'COMMENTS' => '_Comment',
            'PUBLISHER' => '_Publisher',
            'FOLDER' => '_InstallLocation',
            'SOURCE' => '_source',
            'GUID' => '_Guid',
            'LANGUAGE' => '_Language',
            'INSTALLDATE' => $installationDateProtocol,
            'BITSWIDTH' => '_Architecture',
            'FILESIZE' => '_Size',
        ];

        $databaseHydrator = $this->createMock(\Database\Hydrator\Software::class);
        $databaseHydrator->method('extract')->with($software)->willReturn($databaseContent);

        $hydrator = new \Protocol\Hydrator\Software($databaseHydrator);
        $this->assertEquals($agentData, $hydrator->extract($software));
    }
}
