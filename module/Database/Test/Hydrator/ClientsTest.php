<?php
/**
 * Tests for Clients hydrator
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

namespace Database\Test\Hydrator;

require_once 'Database.php'; // from NADA path

class ClientsTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    protected function _getHydrator()
    {
        $nada = $this->getMockBuilder('Nada_Database')->disableOriginalConstructor()->getMockForAbstractClass();
        $nada->method('timestampFormatPhp')->willReturn('Y-m-d H:i:s');

        $hydrator = $this->getMock('Zend\Stdlib\Hydrator\ArraySerializable');
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

        $customFieldManager = $this->getMockBuilder('Model\Client\CustomFieldManager')
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $customFieldManager->method('getHydrator')->willReturn($hydrator);

        $windowsInstallations = $this->getMockBuilder('Database\Table\WindowsInstallations')
                                     ->disableOriginalConstructor()
                                     ->getMock();
        $windowsInstallations->method('getHydrator')->willReturn($hydrator);

        $resultSet = $this->getMockBuilder('Zend\Db\ResultSet\AbstractResultSet')
                          ->setMethods(array('getObjectPrototype'))
                          ->getMockForAbstractClass();
        $resultSet->method('getObjectPrototype')->willReturn($this);

        $itemTable = $this->getMockBuilder('Database\AbstractTable')
                          ->disableOriginalConstructor()
                          ->setMethods(array('getResultSetPrototype', 'getHydrator'))
                          ->getMockForAbstractClass();
        $itemTable->method('getResultSetPrototype')->willReturn($resultSet);
        $itemTable->method('getHydrator')->willReturn($hydrator);

        $itemManager = $this->getMockBuilder('Model\Client\ItemManager')->disableOriginalConstructor()->getMock();
        $itemManager->method('getTable')->willReturnMap(
            array(
                array('item', $itemTable),
                array('ClientsTest', $itemTable),
            )
        );

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->willReturnMap(
            array(
                array('Database\Nada', true, $nada),
                array('Database\Table\WindowsInstallations', true, $windowsInstallations),
                array('Model\Client\CustomFieldManager', true, $customFieldManager),
                array('Model\Client\ItemManager', true, $itemManager),
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
            'smodel' => 'Model',
            'name' => 'Name',
            'useragent' => 'User Agent',
            'description' => 'Os Comment',
            'osname' => "Os Name\xC2\x99",
            'osversion' => 'Os Version Number',
            'oscomments' => 'Os Version String',
            'memory' => 2048,
            'ssn' => 'Serial',
            'swap' => 3000,
            'type' => 'Type',
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
            'ClientId' => 'Client Id',
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
            'Model' => 'Model',
            'Name' => 'Name',
            'UserAgent' => 'User Agent',
            'OsComment' => 'Os Comment',
            'OsName' => "Os Name\xE2\x84\xA2",
            'OsVersionNumber' => 'Os Version Number',
            'OsVersionString' => 'Os Version String',
            'PhysicalMemory' => 2048,
            'Serial' => 'Serial',
            'SwapMemory' => 3000,
            'Type' => 'Type',
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
            'ClientId' => 'Client Id',
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
            'Model' => 'Model',
            'Name' => 'Name',
            'UserAgent' => 'User Agent',
            'OsComment' => 'Os Comment',
            'OsName' => "Os Name\xE2\x84\xA2",
            'OsVersionNumber' => 'Os Version Number',
            'OsVersionString' => 'Os Version String',
            'PhysicalMemory' => 2048,
            'Serial' => 'Serial',
            'SwapMemory' => 3000,
            'Type' => 'Type',
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
            'smodel' => 'Model',
            'name' => 'Name',
            'useragent' => 'User Agent',
            'description' => 'Os Comment',
            'osname' => "Os Name\xE2\x84\xA2",
            'osversion' => 'Os Version Number',
            'oscomments' => 'Os Version String',
            'memory' => 2048,
            'ssn' => 'Serial',
            'swap' => 3000,
            'type' => 'Type',
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
            'ClientId' => 'deviceid',
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
            'Model' => 'smodel',
            'Name' => 'name',
            'UserAgent' => 'useragent',
            'OsComment' => 'description',
            'OsName' => 'osname',
            'OsVersionNumber' => 'osversion',
            'OsVersionString' => 'oscomments',
            'PhysicalMemory' => 'memory',
            'Serial' => 'ssn',
            'SwapMemory' => 'swap',
            'Type' => 'type',
            'UserName' => 'userid',
            'Uuid' => 'uuid',
        );
        $this->assertEquals($expected, $this->_getHydrator()->getExtractorMap());
    }

    public function testHydrateNameInvalid()
    {
        $this->setExpectedException('DomainException', 'Cannot hydrate name: invalid_');
        $this->_getHydrator()->hydrateName('invalid_');
    }

    public function testExtractNameInvalid()
    {
        $this->setExpectedException('DomainException', 'Cannot extract name: invalid');
        $this->_getHydrator()->extractName('invalid');
    }
}
