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
                array(
                    'PROPERTY2' => '0',
                    'PROPERTY3' => 0,
                    'PROPERTY1' => '<value>',
                    'IGNORE1' => '',
                    'IGNORE2' => null,
                ),
                array(),
                array(),
                array(),
                array(),
                'Hardware.xml'
            ),
            array(
                array(),
                array(
                    'PROPERTY2' => '0',
                    'PROPERTY3' => 0,
                    'PROPERTY1' => '<value>',
                    'IGNORE1' => '',
                    'IGNORE2' => null,
                ),
                array(),
                array(),
                array(),
                'Bios.xml'
            ),
            array(
                array(),
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
                'CustomFields.xml'
            ),
            array(
                array(),
                array(),
                array(),
                array('<package1>', 'package2'),
                array(),
                'Packages.xml'
            ),
            array(
                array(),
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
                'ItemSingleFull.xml'
            ),
            array(
                array(),
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
                    'sim' => array(array('property' => 'sim')),
                    'software' => array(array('property' => 'software')),
                    'storagedevice' => array(array('property' => 'storagedevice')),
                    'virtualmachine' => array(array('property' => 'virtualmachine')),
                ),
                'ItemAllBasic.xml'
            ),
        );
    }

    /**
     * @dataProvider loadClientProvider
     */
    public function testLoadClient($hardwareData, $biosData, $customFields, $packages, $items, $xmlFile)
    {
        // Only getTableName() is called which returns static data and does not
        // need to be mocked.
        $itemManager = $this->getMockBuilder('Model\Client\ItemManager')
                            ->disableOriginalConstructor()
                            ->setMethods(null)
                            ->getMock();
        $itemTypes = $itemManager->getItemTypes();

        $itemHydrator = $this->getMock('Zend\Stdlib\Hydrator\ArraySerializable');
        $itemHydrator->method('extract')->will(
            $this->returnCallback(
                function($data) {
                    return array_change_key_case($data, CASE_UPPER);
                }
            )
        );

        $mapGetItems = array();
        $services = array();
        foreach ($itemTypes as $type) {
            $services[] = array(
                'Protocol\Hydrator\\' . $itemManager->getTableName($type),
                true,
                $itemHydrator
            );
            if (isset($items[$type])) {
                $mapGetItems[] = array($type, 'id', 'asc', array(), $items[$type]);
            } else {
                $mapGetItems[] = array($type, 'id', 'asc', array(), array());
            }
        }

        $client = $this->getMock('Model\Client\Client');
        $client->method('offsetGet')->willReturnMap(
            array(
                array('ClientId', 'client_id'),
                array('CustomFields', $customFields),
            )
        );
        $client->method('getDownloadedPackages')->willReturn($packages);
        $client->expects($this->exactly(count($itemTypes)))
               ->method('getItems')
               ->will($this->returnValueMap($mapGetItems));

        $hardwareHydrator = $this->getMockBuilder('Protocol\Hydrator\ClientsHardware')
                                 ->disableOriginalConstructor()
                                 ->getMock();
        $hardwareHydrator->expects($this->once())
                         ->method('extract')
                         ->with($client)
                         ->willReturn($hardwareData);

        $biosHydrator = $this->getMock('Protocol\Hydrator\ClientsBios');
        $biosHydrator->expects($this->once())
                     ->method('extract')
                     ->with($client)
                     ->willReturn($biosData);

        $services[] = array('Model\Client\ItemManager', true, $itemManager);
        $services[] = array('Protocol\Hydrator\ClientsHardware', true, $hardwareHydrator);
        $services[] = array('Protocol\Hydrator\ClientsBios', true, $biosHydrator);

        $serviceLocator = $this->getMock('\Zend\ServiceManager\ServiceManager');
        $serviceLocator->method('get')->willReturnMap($services);

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
