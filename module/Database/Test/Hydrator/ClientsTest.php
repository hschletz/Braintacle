<?php

/**
 * Tests for Clients hydrator
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

use Database\AbstractTable;
use Database\Table\WindowsInstallations;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Model\Client\CustomFieldManager;
use Model\Client\ItemManager;
use PHPUnit\Framework\MockObject\Stub;

class ClientsTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    protected function getHydrator()
    {
        $nada = $this->createMock('Nada\Database\AbstractDatabase');
        $nada->method('timestampFormatPhp')->willReturn('Y-m-d H:i:s');

        $hydrator = $this->createMock(\Laminas\Hydrator\AbstractHydrator::class);
        $hydrator->method('hydrateName')->willReturnMap(
            array(
                array('fields_3', null, 'Custom field'),
                array('winprodid', null, 'ProductId'),
                array('column', null, 'Property'),
            )
        );
        $hydrator->method('hydrateValue')->willReturnMap(
            array(
                array('Custom field', 'custom_extracted', null, 'custom_hydrated'),
                array('ProductId', 'product_id_extracted', null, 'product_id_hydrated'),
                array('Property', 'item_value_extracted', null, 'item_value_hydrated'),
            )
        );

        $customFieldManager = $this->createMock(CustomFieldManager::class);
        $customFieldManager->method('getHydrator')->willReturn($hydrator);

        $windowsInstallations = $this->createMock(WindowsInstallations::class);
        $windowsInstallations->method('getHydrator')->willReturn($hydrator);

        $resultSet = $this->createStub(HydratingResultSet::class);
        $resultSet->method('getObjectPrototype')->willReturn($this);

        $itemTable = $this->createStub(AbstractTable::class);
        $itemTable->method('getResultSetPrototype')->willReturn($resultSet);
        $itemTable->method('getHydrator')->willReturn($hydrator);

        $itemManager = $this->createMock(ItemManager::class);
        $itemManager->method('getTable')->willReturnMap(
            array(
                array('item', $itemTable),
                array('ClientsTest', $itemTable),
            )
        );

        /** @var Stub|ServiceLocatorInterface */
        $serviceManager = $this->createStub(ServiceLocatorInterface::class);
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', $nada),
                array('Database\Table\WindowsInstallations', $windowsInstallations),
                array('Model\Client\CustomFieldManager', $customFieldManager),
                array('Model\Client\ItemManager', $itemManager),
            )
        );

        return new \Database\Hydrator\Clients($serviceManager);
    }

    public function hydrateProvider()
    {
        $extracted = array(
            'id' => 42,
            'assettag' => 'Asset Tag',
            'bdate' => 'Bios Date',
            'bmanufacturer' => 'Bios Manufacturer',
            'bversion' => 'Bios Version',
            'deviceid' => 'Client Id',
            'processors' => 2000,
            'processorn' => 2,
            'processort' => 'Cpu Type',
            'defaultgateway' => '192.0.2.1',
            'dns' => '192.0.2.1',
            'dns_domain' => 'example.net',
            'lastdate' => '2015-08-30 09:01:02',
            'checksum' => 65536,
            'ipaddr' => '192.0.2.2',
            'lastcome' => '2015-08-30 09:02:03',
            'smanufacturer' => 'Manufacturer',
            'smodel' => 'Product name',
            'name' => 'Name',
            'description' => 'Os Comment',
            'osname' => "Os Name\xC2\x99",
            'osversion' => 'Os Version Number',
            'oscomments' => 'Os Version String',
            'memory' => 2048,
            'ssn' => 'Serial',
            'swap' => 3000,
            'type' => 'Type',
            'useragent' => 'User Agent',
            'userid' => 'User_Name',
            'uuid' => 'Uuid',
            'package_status' => 'ERR_STATUS',
            'static' => 'membership',
            'customfields_fields_3' => 'custom_extracted',
            'windows_winprodid' => 'product_id_extracted',
            'registry_content' => 'registry content',
            'item_column' => 'item_value_extracted',
        );
        $hydrated = array(
            'Id' => 42,
            'AssetTag' => 'Asset Tag',
            'BiosDate' => 'Bios Date',
            'BiosManufacturer' => 'Bios Manufacturer',
            'BiosVersion' => 'Bios Version',
            'IdString' => 'Client Id',
            'CpuClock' => 2000,
            'CpuCores' => 2,
            'CpuType' => 'Cpu Type',
            'DefaultGateway' => '192.0.2.1',
            'DnsServer' => '192.0.2.1',
            'DnsDomain' => 'example.net',
            'InventoryDate' => new \DateTime('2015-08-30 11:01:02+02:00'),
            'InventoryDiff' => 65536,
            'IpAddress' => '192.0.2.2',
            'LastContactDate' => new \DateTime('2015-08-30 11:02:03+02:00'),
            'Manufacturer' => 'Manufacturer',
            'Name' => 'Name',
            'OsComment' => 'Os Comment',
            'OsName' => "Os Name\xE2\x84\xA2",
            'OsVersionNumber' => 'Os Version Number',
            'OsVersionString' => 'Os Version String',
            'PhysicalMemory' => 2048,
            'ProductName' => 'Product name',
            'Serial' => 'Serial',
            'SwapMemory' => 3000,
            'Type' => 'Type',
            'UserAgent' => 'User Agent',
            'UserName' => 'User_Name',
            'Uuid' => 'Uuid',
            'Package.Status' => 'ERR_STATUS',
            'Membership' => 'membership',
            'CustomFields.Custom field' => 'custom_hydrated',
            'Windows.ProductId' => 'product_id_hydrated',
            'Registry.Content' => 'registry content',
            'ClientsTest.Property' => 'item_value_hydrated',
        );
        return array(array($extracted, $hydrated));
    }

    public function extractProvider()
    {
        $hydrated = array(
            'Id' => 42,
            'AssetTag' => 'Asset Tag',
            'BiosDate' => 'Bios Date',
            'BiosManufacturer' => 'Bios Manufacturer',
            'BiosVersion' => 'Bios Version',
            'IdString' => 'Client Id',
            'CpuClock' => 2000,
            'CpuCores' => 2,
            'CpuType' => 'Cpu Type',
            'DefaultGateway' => '192.0.2.1',
            'DnsServer' => '192.0.2.1',
            'DnsDomain' => 'example.net',
            'InventoryDate' => new \DateTime('2015-08-30 11:01:02'),
            'InventoryDiff' => 65536,
            'IpAddress' => '192.0.2.2',
            'LastContactDate' => new \DateTime('2015-08-30 11:02:03'),
            'Manufacturer' => 'Manufacturer',
            'Name' => 'Name',
            'OsComment' => 'Os Comment',
            'OsName' => "Os Name\xE2\x84\xA2",
            'OsVersionNumber' => 'Os Version Number',
            'OsVersionString' => 'Os Version String',
            'PhysicalMemory' => 2048,
            'ProductName' => 'Product name',
            'Serial' => 'Serial',
            'SwapMemory' => 3000,
            'Type' => 'Type',
            'UserAgent' => 'User Agent',
            'UserName' => 'User_Name',
            'Uuid' => 'Uuid',
        );
        $extracted = array(
            'id' => 42,
            'assettag' => 'Asset Tag',
            'bdate' => 'Bios Date',
            'bmanufacturer' => 'Bios Manufacturer',
            'bversion' => 'Bios Version',
            'deviceid' => 'Client Id',
            'processors' => 2000,
            'processorn' => 2,
            'processort' => 'Cpu Type',
            'defaultgateway' => '192.0.2.1',
            'dns' => '192.0.2.1',
            'dns_domain' => 'example.net',
            'lastdate' => '2015-08-30 09:01:02',
            'checksum' => 65536,
            'ipaddr' => '192.0.2.2',
            'lastcome' => '2015-08-30 09:02:03',
            'smanufacturer' => 'Manufacturer',
            'smodel' => 'Product name',
            'name' => 'Name',
            'description' => 'Os Comment',
            'osname' => "Os Name\xE2\x84\xA2",
            'osversion' => 'Os Version Number',
            'oscomments' => 'Os Version String',
            'memory' => 2048,
            'ssn' => 'Serial',
            'swap' => 3000,
            'type' => 'Type',
            'useragent' => 'User Agent',
            'userid' => 'User_Name',
            'uuid' => 'Uuid',
        );
        return array(array($hydrated, $extracted));
    }

    public function testGetExtractorMap()
    {
        $expected = array(
            'Id' => 'id',
            'AssetTag' => 'assettag',
            'BiosDate' => 'bdate',
            'BiosManufacturer' => 'bmanufacturer',
            'BiosVersion' => 'bversion',
            'IdString' => 'deviceid',
            'CpuClock' => 'processors',
            'CpuCores' => 'processorn',
            'CpuType' => 'processort',
            'DefaultGateway' => 'defaultgateway',
            'DnsDomain' => 'dns_domain',
            'DnsServer' => 'dns',
            'InventoryDate' => 'lastdate',
            'InventoryDiff' => 'checksum',
            'IpAddress' => 'ipaddr',
            'LastContactDate' => 'lastcome',
            'Manufacturer' => 'smanufacturer',
            'Name' => 'name',
            'OsComment' => 'description',
            'OsName' => 'osname',
            'OsVersionNumber' => 'osversion',
            'OsVersionString' => 'oscomments',
            'PhysicalMemory' => 'memory',
            'ProductName' => 'smodel',
            'Serial' => 'ssn',
            'SwapMemory' => 'swap',
            'Type' => 'type',
            'UserAgent' => 'useragent',
            'UserName' => 'userid',
            'Uuid' => 'uuid',
        );
        $this->assertEquals($expected, $this->getHydrator()->getExtractorMap());
    }

    public function testHydrateNameInvalid()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Cannot hydrate name: invalid_');
        $this->getHydrator()->hydrateName('invalid_');
    }

    public function testExtractNameInvalid()
    {
        $this->expectException('DomainException');
        $this->expectExceptionMessage('Cannot extract name: invalid');
        $this->getHydrator()->extractName('invalid');
    }
}
