<?php
/**
 * Tests for InventoryRequest message
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

class InventoryRequestTest extends \PHPUnit\Framework\TestCase
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
        return [
            [
                [
                    'PROPERTY2' => '0',
                    'PROPERTY3' => 0,
                    'PROPERTY1' => '<value>',
                    'IGNORE1' => '',
                    'IGNORE2' => null,
                ],
                [],
                [],
                [],
                [],
                null,
                'Hardware.xml'
            ],
            [
                [],
                [
                    'PROPERTY2' => '0',
                    'PROPERTY3' => 0,
                    'PROPERTY1' => '<value>',
                    'IGNORE1' => '',
                    'IGNORE2' => null,
                ],
                [],
                [],
                [],
                null,
                'Bios.xml'
            ],
            [
                [],
                [],
                [],
                [],
                [],
                [
                    'PROPERTY1' => '<value>',
                    'PROPERTY2' => '0',
                    'PROPERTY3' => 0,
                ],
                'Android.xml'
            ],
            [
                [],
                [],
                [
                    'text' => '<value>',
                    'empty' => '',
                    'null' => null,
                    'zero' => 0,
                    'date' => new \DateTime('2015-05-15'),
                ],
                [],
                [],
                null,
                'CustomFields.xml'
            ],
            [
                [],
                [],
                [],
                ['<package1>', 'package2'],
                [],
                null,
                'Packages.xml'
            ],
            [
                [],
                [],
                [],
                [],
                [
                    'filesystem' => [
                        [
                            'property1' => '0',
                            'property2' => '',
                            'property3' => 0,
                            'property4' => null,
                        ],
                        ['property1' => '<value>'],
                    ]
                ],
                null,
                'ItemSingleFull.xml'
            ],
            [
                [],
                [],
                [],
                [],
                [
                    'audiodevice' => [['property' => 'audiodevice']],
                    'cpu' => [['property' => 'cpu']],
                    'controller' => [['property' => 'controller']],
                    'display' => [['property' => 'display']],
                    'displaycontroller' => [['property' => 'displaycontroller']],
                    'extensionslot' => [['property' => 'extensionslot']],
                    'filesystem' => [['property' => 'filesystem']],
                    'inputdevice' => [['property' => 'inputdevice']],
                    'memoryslot' => [['property' => 'memoryslot']],
                    'modem' => [['property' => 'modem']],
                    'msofficeproduct' => [['property' => 'msofficeproduct']],
                    'networkinterface' => [['property' => 'networkinterface']],
                    'port' => [['property' => 'port']],
                    'printer' => [['property' => 'printer']],
                    'registrydata' => [['property' => 'registrydata']],
                    'sim' => [['property' => 'sim']],
                    'software' => [['property' => 'software']],
                    'storagedevice' => [['property' => 'storagedevice']],
                    'virtualmachine' => [['property' => 'virtualmachine']],
                ],
                null,
                'ItemAllBasic.xml'
            ],
        ];
    }

    /**
     * @dataProvider loadClientProvider
     */
    public function testLoadClient(
        $hardwareData,
        $biosData,
        $customFields,
        $packages,
        $items,
        $androidData,
        $xmlFile
    ) {
        // Only getTableName() is called which returns static data and does not
        // need to be mocked.
        $itemManager = $this->getMockBuilder('Model\Client\ItemManager')
                            ->disableOriginalConstructor()
                            ->setMethods(null)
                            ->getMock();
        $itemTypes = $itemManager->getItemTypes();

        $itemHydrator = $this->createMock('Zend\Hydrator\ArraySerializable');
        $itemHydrator->method('extract')->will(
            $this->returnCallback(
                function ($data) {
                    return array_change_key_case($data, CASE_UPPER);
                }
            )
        );

        $mapGetItems = array();
        $services = array();
        foreach ($itemTypes as $type) {
            $services[] = array(
                'Protocol\Hydrator\\' . $itemManager->getTableName($type),
                $itemHydrator
            );
            if (isset($items[$type])) {
                $mapGetItems[] = array($type, 'id', 'asc', array(), $items[$type]);
            } else {
                $mapGetItems[] = array($type, 'id', 'asc', array(), array());
            }
        }

        $androidInstallation = $androidData ? $this->createMock('Model\Client\AndroidInstallation') : null;

        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->willReturnMap([
            ['IdString', 'id_string'],
            ['Android', $androidInstallation],
            ['CustomFields', $customFields],
        ]);
        $client->method('getDownloadedPackageIds')->willReturn($packages);
        $client->expects($this->exactly(count($itemTypes)))
               ->method('getItems')
               ->will($this->returnValueMap($mapGetItems));

        $hardwareHydrator = $this->createMock('Protocol\Hydrator\ClientsHardware');
        $hardwareHydrator->expects($this->once())
                         ->method('extract')
                         ->with($client)
                         ->willReturn($hardwareData);

        $biosHydrator = $this->createMock('Protocol\Hydrator\ClientsBios');
        $biosHydrator->expects($this->once())
                     ->method('extract')
                     ->with($client)
                     ->willReturn($biosData);

        $androidInstallationHydrator = $this->createMock('Zend\Hydrator\HydratorInterface');
        if ($androidData) {
            $androidInstallationHydrator->expects($this->once())
                                        ->method('extract')
                                        ->with($androidInstallation)
                                        ->willReturn($androidData);
        } else {
            $androidInstallationHydrator->expects($this->never())->method('extract');
        }

        $services[] = ['Model\Client\ItemManager', $itemManager];
        $services[] = ['Protocol\Hydrator\ClientsHardware', $hardwareHydrator];
        $services[] = ['Protocol\Hydrator\ClientsBios', $biosHydrator];
        $services[] = ['Protocol\Hydrator\AndroidInstallations', $androidInstallationHydrator];

        $serviceLocator = $this->createMock('\Zend\ServiceManager\ServiceManager');
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
        $this->expectException(
            'UnexpectedValueException',
            '!Name2015-06-04-18-22-06 is not a valid filename part'
        );
        $document = new \Protocol\Message\InventoryRequest;
        $document->appendChild($document->createElement('DEVICEID', '!Name2015-06-04-18-22-06'));
        $document->getFilename();
    }

    public function testGetFilenameElementNotSet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('DEVICEID element has not been set');
        $document = new \Protocol\Message\InventoryRequest;
        $document->getFilename();
    }
}
