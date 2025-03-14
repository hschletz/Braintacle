<?php

/**
 * Tests for ClientsHardware hydrator
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use AssertionError;
use DateTime;
use Model\Client\Client;
use Model\Client\WindowsInstallation;
use PHPUnit\Framework\TestCase;
use stdClass;

class ClientsHardwareTest extends TestCase
{
    /**
     * WindowsInstallation prototype injected into hydrator
     */
    private WindowsInstallation $_windowsInstallation;

    public function setUp(): void
    {
        $this->_windowsInstallation = $this->createMock('Model\Client\WindowsInstallation');
    }

    protected function getHydrator()
    {
        return new \Protocol\Hydrator\ClientsHardware($this->_windowsInstallation);
    }

    public static function hydrateProvider()
    {
        $extracted = array(
            'CHECKSUM' => 65535,
            'DEFAULTGATEWAY' => '192.0.2.1',
            'DESCRIPTION' => 'os comment',
            'DNS' => '192.0.2.2',
            'IPADDR' => '192.0.2.3',
            'LASTCOME' => '2015-09-02 20:50:23',
            'LASTDATE' => '2015-09-02 20:51:22',
            'MEMORY' => 2048,
            'NAME' => 'name',
            'OSCOMMENTS' => 'os version string',
            'OSNAME' => 'os name',
            'OSVERSION' => 'os version number',
            'PROCESSORN' => 2,
            'PROCESSORS' => 2000,
            'PROCESSORT' => 'cpu type',
            'SWAP' => 2222,
            'USERID' => 'user name',
            'UUID' => 'uuid',
        );
        $extractedWindows = array(
            'ARCH' => 'CPU architecture',
            'USERDOMAIN' => 'user domain',
            'WINCOMPANY' => 'company',
            'WINOWNER' => 'owner',
            'WINPRODID' => 'product id',
            'WINPRODKEY' => 'product key',
            'WORKGROUP' => 'workgroup',
            'IGNORED' => 'ignored',
        );
        $hydrated = [
            'inventoryDiff' => 65535,
            'defaultGateway' => '192.0.2.1',
            'osComment' => 'os comment',
            'dnsServer' => '192.0.2.2',
            'ipAddress' => '192.0.2.3',
            'lastContactDate' => new DateTime('2015-09-02 22:50:23+02:00'),
            'inventoryDate' => new DateTime('2015-09-02 22:51:22+02:00'),
            'physicalMemory' => 2048,
            'name' => 'name',
            'osVersionString' => 'os version string',
            'osName' => 'os name',
            'osVersionNumber' => 'os version number',
            'cpuCores' => 2,
            'cpuClock' => 2000,
            'cpuType' => 'cpu type',
            'swapMemory' => 2222,
            'userName' => 'user name',
            'uuid' => 'uuid',
            'idString' => 'ignored',
        ];
        $hydratedWindows = [
            'UserDomain' => 'user domain',
            'Company' => 'company',
            'Owner' => 'owner',
            'ProductId' => 'product id',
            'ProductKey' => 'product key',
            'Workgroup' => 'workgroup',
            'CpuArchitecture' => 'CPU architecture',
        ];
        return [
            [$extracted + ['WORKGROUP' => 'domain'], $hydrated + ['dnsDomain' => 'domain']],
            [$extracted + $extractedWindows, $hydrated + ['windows' => $hydratedWindows]],
        ];
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrate(array $data, array $objectData)
    {
        if (isset($objectData['windows'])) {
            // Set up prototype with new mock object to validate hydrated data.
            $this->_windowsInstallation = $this->createMock(WindowsInstallation::class);
            $this->_windowsInstallation->expects($this->once())->method('exchangeArray')->with($objectData['windows']);
        }

        $hydrator = $this->getHydrator();
        $object = new Client();
        $object->idString = 'ignored';
        $this->assertSame($object, $hydrator->hydrate($data, $object));

        unset($objectData['windows']); // not a regular property
        $this->assertEquals($objectData, get_object_vars($object));
    }

    public static function extractProvider()
    {
        $hydrated = [
            'inventoryDiff' => 65535,
            'defaultGateway' => '192.0.2.1',
            'osComment' => 'os comment',
            'dnsServer' => '192.0.2.2',
            'ipAddress' => '192.0.2.3',
            'lastContactDate' => new DateTime('2015-09-02 22:50:23'),
            'inventoryDate' => new DateTime('2015-09-02 22:51:22'),
            'physicalMemory' => 2048,
            'name' => 'name',
            'osVersionString' => 'os version string',
            'osName' => 'os name',
            'osVersionNumber' => 'os version number',
            'cpuCores' => 2,
            'cpuClock' => 2000,
            'cpuType' => 'cpu type',
            'swapMemory' => 2222,
            'userName' => 'user name',
            'uuid' => 'uuid',
            'idString' => 'ignored',
        ];
        $windowsHydrated = [
            'windows' => [
                'UserDomain' => 'user domain',
                'Company' => 'company',
                'Owner' => 'owner',
                'ProductId' => 'product id',
                'ProductKey' => 'product key',
                'Workgroup' => 'workgroup',
                'CpuArchitecture' => 'CPU architecture',
                'Ignored' => 'ignored',
            ],
        ];
        $extracted = array(
            'CHECKSUM' => 65535,
            'DEFAULTGATEWAY' => '192.0.2.1',
            'DESCRIPTION' => 'os comment',
            'DNS' => '192.0.2.2',
            'IPADDR' => '192.0.2.3',
            'LASTCOME' => '2015-09-02 20:50:23',
            'LASTDATE' => '2015-09-02 20:51:22',
            'MEMORY' => 2048,
            'NAME' => 'name',
            'OSCOMMENTS' => 'os version string',
            'OSNAME' => 'os name',
            'OSVERSION' => 'os version number',
            'PROCESSORN' => 2,
            'PROCESSORS' => 2000,
            'PROCESSORT' => 'cpu type',
            'SWAP' => 2222,
            'USERID' => 'user name',
            'UUID' => 'uuid',
        );
        $windowsExtracted = array(
            'ARCH' => 'CPU architecture',
            'USERDOMAIN' => 'user domain',
            'WINCOMPANY' => 'company',
            'WINOWNER' => 'owner',
            'WINPRODID' => 'product id',
            'WINPRODKEY' => 'product key',
            'WORKGROUP' => 'workgroup',
        );
        return [
            [
                $hydrated + ['windows' => null, 'dnsDomain' => 'domain'],
                $extracted + ['WORKGROUP' => 'domain']
            ], // UNIX client
            [$hydrated + $windowsHydrated, $extracted + $windowsExtracted], // Windows client
        ];
    }

    /**
     * @dataProvider extractProvider
     */
    public function testExtract(array $objectData, array $data)
    {
        $hydrator = $this->getHydrator();
        $object = new Client();
        foreach ($objectData as $key => $value) {
            $object->$key = $value;
        }
        $this->assertEquals($data, $hydrator->extract($object));
    }

    public function testExtractInvalidClass()
    {
        $this->expectException(AssertionError::class);
        $hydrator = $this->getHydrator();
        $hydrator->extract(new stdClass());
    }
}
