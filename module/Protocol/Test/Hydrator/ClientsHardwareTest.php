<?php
/**
 * Tests for ClientsHardware hydrator
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

namespace Protocol\Test\Hydrator;

class ClientsHardwareTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    public function testService()
    {
        $this->assertInstanceOf(
            'Protocol\Hydrator\ClientsHardware',
            \Library\Application::getService('Protocol\Hydrator\ClientsHardware')
        );
    }

    public function hydrateProvider()
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
            'OSNAME' => "os name\xC2\x99",
            'OSVERSION' => 'os version number',
            'PROCESSORN' => 2,
            'PROCESSORS' => 2000,
            'PROCESSORT' => 'cpu type',
            'SWAP' => 2222,
            'USERID' => 'user name',
            'UUID' => 'uuid',
            'USERDOMAIN' => 'user domain',
            'WINCOMPANY' => 'company',
            'WINOWNER' => 'owner',
            'WINPRODID' => 'product id',
            'WINPRODKEY' => 'product key',
            'WORKGROUP' => 'workgroup',
            'IGNORED' => 'ignored',
        );
        $hydrated = array(
            'InventoryDiff' => 65535,
            'DefaultGateway' => '192.0.2.1',
            'OsComment' => 'os comment',
            'DnsServer' => '192.0.2.2',
            'IpAddress' => '192.0.2.3',
            'LastContactDate' => new \DateTime('2015-09-02 20:50:23'),
            'InventoryDate' => new \DateTime('2015-09-02 20:51:22'),
            'PhysicalMemory' => 2048,
            'Name' => 'name',
            'OsVersionString' => 'os version string',
            'OsName' => "os name\xE2\x84\xA2",
            'OsVersionNumber' => 'os version number',
            'CpuCores' => 2,
            'CpuClock' => 2000,
            'CpuType' => 'cpu type',
            'SwapMemory' => 2222,
            'UserName' => 'user name',
            'Uuid' => 'uuid',
            'Windows' => array(
                'UserDomain' => 'user domain',
                'Company' => 'company',
                'Owner' => 'owner',
                'ProductId' => 'product id',
                'ProductKey' => 'product key',
                'Workgroup' => 'workgroup',
            ),
            'ClientId' => 'ignored',
        );
        return array(array($extracted, $hydrated));
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrate(array $data, array $objectData)
    {
        $hydrator = $this->_getHydrator();
        $object = new \ArrayObject;
        $object['ClientId'] = 'ignored';
        $this->assertSame($object, $hydrator->hydrate($data, $object));
        $this->assertEquals($objectData, $object->getArrayCopy());
    }

    public function extractProvider()
    {
        $hydrated = array(
            'InventoryDiff' => 65535,
            'DefaultGateway' => '192.0.2.1',
            'OsComment' => 'os comment',
            'DnsServer' => '192.0.2.2',
            'IpAddress' => '192.0.2.3',
            'LastContactDate' => new \DateTime('2015-09-02 20:50:23'),
            'InventoryDate' => new \DateTime('2015-09-02 20:51:22'),
            'PhysicalMemory' => 2048,
            'Name' => 'name',
            'OsVersionString' => 'os version string',
            'OsName' => "os name\xE2\x84\xA2",
            'OsVersionNumber' => 'os version number',
            'CpuCores' => 2,
            'CpuClock' => 2000,
            'CpuType' => 'cpu type',
            'SwapMemory' => 2222,
            'UserName' => 'user name',
            'Uuid' => 'uuid',
            'ClientId' => 'ignored',
        );
        $windowsHydrated = array(
            'Windows' => array(
                'UserDomain' => 'user domain',
                'Company' => 'company',
                'Owner' => 'owner',
                'ProductId' => 'product id',
                'ProductKey' => 'product key',
                'Workgroup' => 'workgroup',
                'Ignored' => 'ignored',
            ),
        );
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
            'OSNAME' => "os name\xE2\x84\xA2",
            'OSVERSION' => 'os version number',
            'PROCESSORN' => 2,
            'PROCESSORS' => 2000,
            'PROCESSORT' => 'cpu type',
            'SWAP' => 2222,
            'USERID' => 'user name',
            'UUID' => 'uuid',
        );
        $windowsExtracted = array(
            'USERDOMAIN' => 'user domain',
            'WINCOMPANY' => 'company',
            'WINOWNER' => 'owner',
            'WINPRODID' => 'product id',
            'WINPRODKEY' => 'product key',
            'WORKGROUP' => 'workgroup',
        );
        return array(
            array($hydrated + array('Windows' => null), $extracted), // UNIX client
            array($hydrated + $windowsHydrated, $extracted + $windowsExtracted), // Windows client
        );
    }
}
