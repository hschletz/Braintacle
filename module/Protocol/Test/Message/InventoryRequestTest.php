<?php
/**
 * Tests for InventoryRequest message
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

namespace Protocol\Test\Message;

class InventoryRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSchemaFilename()
    {
        $document = new \Protocol\Message\InventoryRequest;
        $this->assertEquals(
            realpath(__DIR__ . '/../../data/RelaxNG/InventoryRequest.rng'),
            $document->getSchemaFilename()
        );
    }

    public function loadClientProvider()
    {
        return array(
            array(
                array(),
                array(),
                array(),
                array(),
                null,
                'Minimal.xml'
            ),
            array(
                array(
                    'CpuClock' => 'cpu_clock',
                    'CpuCores' => 'cpu_cores',
                    'CpuType' => 'cpu_type',
                    'DefaultGateway' => 'default_gateway',
                    'DnsServer' => 'dns_server',
                    'InventoryDiff' => 'inventory_diff',
                    'IpAddress' => 'ip_address',
                    'Name' => 'name',
                    'OsComment' => '<os_comment>',
                    'OsName' => 'os_name',
                    'OsVersionNumber' => 'os_version_number',
                    'OsVersionString' => 'os_version_string',
                    'PhysicalMemory' => 'physical_memory',
                    'SwapMemory' => 'swap_memory',
                    'UserName' => 'user_name',
                    'Uuid' => 'uuid',
                    'Workgroup' => 'workgroup',
                ),
                array(),
                array(),
                array(),
                null,
                'ClientModelHardwareFull.xml'
            ),
            array(
                array('Type' => '<type>', 'Serial' => 0),
                array(),
                array(),
                array(),
                null,
                'BiosPartial.xml'
            ),
            array(
                array(
                    'AssetTag' => 'asset_tag',
                    'BiosDate' => 'bios_date',
                    'BiosManufacturer' => 'bios_manufacturer',
                    'BiosVersion' => 'bios_version',
                    'Manufacturer' => 'manufacturer',
                    'Model' => 'model',
                    'Serial' => 'serial',
                    'Type' => 'type',
                ),
                array(),
                array(),
                array(),
                null,
                'BiosFull.xml'
            ),
            array(
                array(),
                array(),
                array(),
                array(),
                array(
                    'UserDomain' => '',
                    'Company' => '',
                    'Owner' => '',
                    'ProductId' => '0',
                    'ProductKey' => null,
                ),
                'WindowsPartial.xml'
            ),
            array(
                array(),
                array(),
                array(),
                array(),
                array(
                    'UserDomain' => 'user_domain',
                    'Company' => 'company',
                    'Owner' => 'owner',
                    'ProductId' => '<product_id>',
                    'ProductKey' => 'product_key',
                ),
                'WindowsFull.xml'
            ),
            array(
                array(),
                array(
                    'text' => '<value>',
                    'empty' => '',
                    'null' => null,
                    'zero' => 0,
                    'date' => new \DateTime('2015-05-15'),
                ),
                array(),
                array(),
                null,
                'CustomFields.xml'
            ),
            array(
                array(),
                array(),
                array('<package1>', 'package2'),
                array(),
                null,
                'Packages.xml'
            ),
            array(
                array(),
                array(),
                array(),
                array(
                    'filesystem' => array(
                        array(
                            'property1' => '0',
                            'property2' => '',
                            'property3' => 0,
                            'property4' => null,
                        ),
                        array('property1' => '<value>'),
                    )
                ),
                null,
                'ItemSingleFull.xml'
            ),
            array(
                array(),
                array(),
                array(),
                array(
                    'audiodevice' => array(array('property' => 'audiodevice')),
                    'controller' => array(array('property' => 'controller')),
                    'display' => array(array('property' => 'display')),
                    'displaycontroller' => array(array('property' => 'displaycontroller')),
                    'extensionslot' => array(array('property' => 'extensionslot')),
                    'filesystem' => array(array('property' => 'filesystem')),
                    'inputdevice' => array(array('property' => 'inputdevice')),
                    'memoryslot' => array(array('property' => 'memoryslot')),
                    'modem' => array(array('property' => 'modem')),
                    'msofficeproduct' => array(array('property' => 'msofficeproduct')),
                    'networkinterface' => array(array('property' => 'networkinterface')),
                    'port' => array(array('property' => 'port')),
                    'printer' => array(array('property' => 'printer')),
                    'registrydata' => array(array('property' => 'registrydata')),
                    'software' => array(array('property' => 'software')),
                    'storagedevice' => array(array('property' => 'storagedevice')),
                    'virtualmachine' => array(array('property' => 'virtualmachine')),
                ),
                null,
                'ItemAllBasic.xml'
            ),
        );
    }

    /**
     * @dataProvider loadClientProvider
     */
    public function testLoadClient($clientData, $customFields, $packages, $items, $windows, $xmlFile)
    {
        $clientData['ClientId'] = 'client_id';
        $clientData['InventoryDate'] = new \Zend_Date('2015-05-15 21:19:05');
        $clientData['LastContactDate'] = new \Zend_Date('2015-05-05 21:18:04');
        $clientData['CustomFields'] = $customFields;
        $clientData['Windows'] = $windows;

        // Only getTableName() is called which returns static data and does not
        // need to be mocked.
        $itemManager = $this->getMockBuilder('Model\Client\ItemManager')
                            ->disableOriginalConstructor()
                            ->setMethods(null)
                            ->getMock();
        $itemTypes = $itemManager->getItemTypes();

        $hydrator = $this->getMock('Zend\Stdlib\Hydrator\ArraySerializable');
        $hydrator->method('extract')->will(
            $this->returnCallback(
                function($data) {
                    return array_change_key_case($data, CASE_UPPER);
                }
            )
        );

        $serviceLocator = new \Zend\ServiceManager\ServiceManager;
        $serviceLocator->setService('Model\Client\ItemManager', $itemManager);

        $mapOffsetGet = array();
        $mapGetProperty = array();
        $mapGetItems = array();
        foreach ($clientData as $key => $value) {
            $mapOffsetGet[] = array($key, $value);
            $mapGetProperty[] = array($key, true, $value);
        }
        foreach ($itemTypes as $type) {
            $serviceLocator->setService(
                'Protocol\Hydrator\\' . $itemManager->getTableName($type),
                $hydrator
            );
            if (isset($items[$type])) {
                $mapGetItems[] = array($type, 'id', 'asc', array(), $items[$type]);
            } else {
                $mapGetItems[] = array($type, 'id', 'asc', array(), array());
            }
        }
        $client = $this->getMock('Model_Computer');
        $client->method('offsetGet')->will($this->returnValueMap($mapOffsetGet));
        $client->method('getProperty')->will($this->returnValueMap($mapGetProperty));
        $client->method('getDownloadedPackages')->willReturn($packages);
        $client->expects($this->exactly(count($itemTypes)))
               ->method('getItems')
               ->will($this->returnValueMap($mapGetItems));

        $document = new \Protocol\Message\InventoryRequest;
        $document->loadClient($client, $serviceLocator);

        $this->assertXmlStringEqualsXmlFile(
            \Protocol\Module::getPath('data/Test/Message/InventoryRequest/' . $xmlFile),
            $document->saveXml()
        );
    }

    public function testGetFilename()
    {
        $document = new \Protocol\Message\InventoryRequest;
        $document->appendChild($document->createElement('DEVICEID', 'Name-2015-06-04-18-22-06'));
        $this->assertEquals('Name-2015-06-04-18-22-06.xml', $document->getFilename());
    }

    public function testGetFilenameInvalidName()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            '!Name2015-06-04-18-22-06 is not a valid filename part'
        );
        $document = new \Protocol\Message\InventoryRequest;
        $document->appendChild($document->createElement('DEVICEID', '!Name2015-06-04-18-22-06'));
        $document->getFilename();
    }

    public function testGetFilenameElementNotSet()
    {
        $this->setExpectedException('LogicException', 'DEVICEID element has not been set');
        $document = new \Protocol\Message\InventoryRequest;
        $document->getFilename();
    }
}
