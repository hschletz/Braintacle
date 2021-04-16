<?php

/**
 * Tests for Content element
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

namespace Protocol\Test\Message\InventoryRequest;

use Interop\Container\ContainerInterface;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\ItemManager;
use Protocol\Hydrator;
use Protocol\Message\InventoryRequest\Content;
use TheSeer\fDOM\fDOMElement;
use Laminas\Hydrator\HydratorInterface;
use Mockery;

class ContentTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testConstructor()
    {
        $content = new Content($this->createStub(ContainerInterface::class));
        $this->assertEquals('CONTENT', $content->tagName);
    }

    public function testAppendSections()
    {
        $content = $this->createPartialMock(
            Content::class,
            [
                'appendSystemSection',
                'appendOsSpecificSection',
                'appendAccountinfoSection',
                'appendDownloadSection',
                'appendAllItemSections'
            ]
        );
        $content->expects($this->exactly(2))
                ->method('appendSystemSection')
                ->withConsecutive([Content::SYSTEM_SECTION_HARDWARE], [Content::SYSTEM_SECTION_BIOS]);
        $content->expects($this->once())->method('appendOsSpecificSection');
        $content->expects($this->once())->method('appendAccountinfoSection');
        $content->expects($this->once())->method('appendDownloadSection');
        $content->expects($this->once())->method('appendAllItemSections');

        $content->appendSections($this->createStub(Client::class));
    }

    public function appendSystemSectionProvider()
    {
        return [
            [Content::SYSTEM_SECTION_HARDWARE, Hydrator\ClientsHardware::class],
            [Content::SYSTEM_SECTION_BIOS, Hydrator\ClientsBios::class],
        ];
    }

    /** @dataProvider appendSystemSectionProvider */
    public function testAppendSystemSection($section, $hydratorService)
    {
        $data = [
            'name4' => '',
            'name3' => 'value3',
            'name2' => null,
            'name1' => 'value1',
        ];

        $client = $this->createStub(Client::class);

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')->with($client)->willReturn($data);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with($hydratorService)->willReturn($hydrator);

        $element = $this->createMock(fDOMElement::class);
        $element->expects($this->exactly(2))
                ->method('appendElement')
                ->withConsecutive(['name1', 'value1', true], ['name3', 'value3', true]);

        $content = Mockery::mock(Content::class, [$container])->makePartial();
        $content->shouldReceive('createElement')->with($section)->andReturn($element);
        $content->shouldReceive('appendChild')->once()->with($element);

        $content->setClient($client);
        $content->appendSystemSection($section);
    }

    public function testAppendSectionsInvalidSection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid section name: foo');
        $content = new Content($this->createStub(ContainerInterface::class));
        $content->appendSystemSection('foo');
    }

    public function testAppendOsSpecificSectionAndroid()
    {
        $android = $this->createStub(AndroidInstallation::class);
        $data = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];

        $client = $this->createMock(Client::class);
        $client->method('offsetGet')->with('Android')->willReturn($android);

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')->with($android)->willReturn($data);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(Hydrator\AndroidInstallations::class)->willReturn($hydrator);

        $element = $this->createMock(fDOMElement::class);
        $element->expects($this->exactly(2))
                ->method('appendElement')
                ->withConsecutive(['name1', 'value1', true], ['name2', 'value2', true]);

        $content = Mockery::mock(Content::class, [$container])->makePartial();
        $content->shouldReceive('appendElement')->with('JAVAINFOS')->andReturn($element);

        $content->setClient($client);
        $content->appendOsSpecificSection();
    }

    public function testAppendOsSpecificSectionOther()
    {
        $client = $this->createMock(Client::class);
        $client->method('offsetGet')->with('Android')->willReturn(null);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $content = Mockery::mock(Content::class, [$container])->makePartial();
        $content->shouldNotReceive('appendElement');

        $content->setClient($client);
        $content->appendOsSpecificSection();
    }

    public function testAppendAccountinfoSection()
    {
        $data = [
            'name1' => 'value1',
            'name2' => '',
            'name3' => null,
            'name4' => new \DateTime('2020-12-27'),
        ];

        $client = $this->createMock(Client::class);
        $client->method('offsetGet')->with('CustomFields')->willReturn($data);

        $element1 = $this->createMock(fDOMElement::class);
        $element1->expects($this->exactly(2))
                 ->method('appendElement')
                 ->withConsecutive(['KEYNAME', 'name1', true], ['KEYVALUE', 'value1', true]);

        $element4 = $this->createMock(fDOMElement::class);
        $element4->expects($this->exactly(2))
                 ->method('appendElement')
                 ->withConsecutive(['KEYNAME', 'name4', true], ['KEYVALUE', '2020-12-27', true]);

        $content = $this->createPartialMock(Content::class, ['appendElement']);
        $content->expects($this->exactly(2))
                ->method('appendElement')
                ->with('ACCOUNTINFO')
                ->willReturnOnConsecutiveCalls($element1, $element4);

        $content->setClient($client);
        $content->appendAccountinfoSection();
    }

    public function testAppendDownloadSection()
    {
        $data = [23, 42];

        $client = $this->createStub(Client::class);
        $client->method('getDownloadedPackageIds')->willReturn($data);

        $package1 = $this->createMock(fDOMElement::class);
        $package1->expects($this->once())->method('setAttribute')->with('ID', 23);

        $package2 = $this->createMock(fDOMElement::class);
        $package2->expects($this->once())->method('setAttribute')->with('ID', 42);

        $history = $this->createMock(fDOMElement::class);
        $history->method('appendElement')->with('PACKAGE')->willReturnOnConsecutivecalls($package1, $package2);

        $download = $this->createMock(fDOMElement::class);
        $download->method('appendElement')->with('HISTORY')->willReturn($history);

        $content = $this->createPartialMock(Content::class, ['appendElement']);
        $content->method('appendElement')->with('DOWNLOAD')->willReturn($download);

        $content->setClient($client);
        $content->appendDownloadSection();
    }

    public function testAppendDownloadSectionNoData()
    {
        $data = [];

        $client = $this->createStub(Client::class);
        $client->method('getDownloadedPackageIds')->willReturn($data);

        $content = $this->createPartialMock(Content::class, ['appendElement']);
        $content->expects($this->never())->method('appendElement');

        $content->setClient($client);
        $content->appendDownloadSection();
    }

    public function testAppendAllItemSections()
    {
        $args = [
            ['controller', 'CONTROLLERS'],
            ['cpu', 'CPUS'],
            ['filesystem', 'DRIVES'],
            ['inputdevice', 'INPUTS'],
            ['memoryslot', 'MEMORIES'],
            ['modem', 'MODEMS'],
            ['display', 'MONITORS'],
            ['networkinterface', 'NETWORKS'],
            ['msofficeproduct', 'OFFICEPACK'],
            ['port', 'PORTS'],
            ['printer', 'PRINTERS'],
            ['registrydata', 'REGISTRY'],
            ['sim', 'SIM'],
            ['extensionslot', 'SLOTS'],
            ['software', 'SOFTWARES'],
            ['audiodevice', 'SOUNDS'],
            ['storagedevice', 'STORAGES'],
            ['displaycontroller', 'VIDEOS'],
            ['virtualmachine', 'VIRTUALMACHINES'],
        ];

        $content = $this->createPartialMock(Content::class, ['appendItemSections']);
        $content->expects($this->exactly(count($args)))->method('appendItemSections')->withConsecutive(...$args);

        $content->appendAllItemSections();
    }

    public function testAppendItemSections()
    {
        $item0 = [
            'key' => null,
        ];
        $item1 = [
            'key1' => null,
            'key2' => 'value2',
            'key3' => '',
            'key4' => 'value4',
        ];

        // Array of hydrated items
        $items = [(object) $item0, (object) $item1];

        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('item_type', 'id', 'asc')->willReturn($items);

        $itemManager = $this->createMock(ItemManager::class);
        $itemManager->method('getTableName')->with('item_type')->willReturn('table_name');

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')
                 ->withConsecutive([$items[0]], [$items[1]])
                 ->willReturnOnConsecutiveCalls($item0, $item1);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            [ItemManager::class, $itemManager],
            ['Protocol\Hydrator\table_name', $hydrator]
        ]);

        $element0 = $this->createMock(fDOMElement::class);
        $element0->expects($this->never())->method('appendElement');

        $element1 = $this->createMock(fDOMElement::class);
        $element1->expects($this->exactly(2))
                 ->method('appendElement')
                 ->withConsecutive(
                     ['key2', 'value2', true],
                     ['key4', 'value4', true]
                 );

        $content = Mockery::mock(Content::class, [$container])->makePartial();
        $content->shouldReceive('appendElement')->with('section_name')->andReturn($element0, $element1);

        $content->setClient($client);
        $content->appendItemSections('item_type', 'section_name');
    }
}
