<?php

/**
 * Tests for Content element
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use ArrayObject;
use Braintacle\Client\Exporter;
use Laminas\Db\ResultSet\ResultSet;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\ItemManager;
use Protocol\Hydrator;
use Protocol\Message\InventoryRequest\Content;
use Laminas\Hydrator\HydratorInterface;
use Mockery;
use Mockery\Mock;
use PhpBench\Dom\Document;
use PhpBench\Dom\Element;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class ContentTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testConstructor()
    {
        $content = new Content($this->createStub(ContainerInterface::class));
        $this->assertEquals('CONTENT', $content->tagName);
    }

    public function testAppendSections()
    {
        /** @var Mock|Content */
        $content = Mockery::mock(Content::class)->makePartial();
        $content->shouldReceive('appendSystemSection')->once()->with(Content::SYSTEM_SECTION_HARDWARE);
        $content->shouldReceive('appendSystemSection')->once()->with(Content::SYSTEM_SECTION_BIOS);
        $content->shouldReceive('appendOsSpecificSection')->once();
        $content->shouldReceive('appendAccountinfoSection')->once();
        $content->shouldReceive('appendDownloadSection')->once();
        $content->shouldReceive('appendAllItemSections')->once();

        $content->appendSections($this->createStub(Client::class));
    }

    public static function appendSystemSectionProvider()
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

        $content = Mockery::mock(Content::class, [$container])->makePartial();
        $content->shouldReceive('appendSection')->once()->with($section, $data);

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

        $exporter = $this->createMock(Exporter::class);
        $exporter->method('getHydrator')->willReturn($hydrator);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(Exporter::class)->willReturn($exporter);

        $content = Mockery::mock(Content::class, [$container])->makePartial();
        $content->shouldReceive('appendSection')->once()->with('JAVAINFOS', $data);

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
        $content->shouldNotReceive('appendSection');

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

        /** @var Mock|Content */
        $content = Mockery::mock(Content::class)->makePartial();
        $content->shouldReceive('appendSection')->once()->with(
            'ACCOUNTINFO',
            ['KEYNAME' => 'name1', 'KEYVALUE' => 'value1'],
        );
        $content->shouldReceive('appendSection')->once()->with(
            'ACCOUNTINFO',
            ['KEYNAME' => 'name4', 'KEYVALUE' => '2020-12-27'],
        );

        $content->setClient($client);
        $content->appendAccountinfoSection();
    }

    public function testAppendDownloadSection()
    {
        $data = [23, 42];

        $client = $this->createStub(Client::class);
        $client->method('getDownloadedPackageIds')->willReturn($data);

        $package1 = $this->createMock(Element::class);
        $package1->expects($this->once())->method('setAttribute')->with('ID', 23);

        $package2 = $this->createMock(Element::class);
        $package2->expects($this->once())->method('setAttribute')->with('ID', 42);

        $history = $this->createMock(Element::class);
        $history->method('appendElement')->with('PACKAGE')->willReturnOnConsecutivecalls($package1, $package2);

        $download = $this->createMock(Element::class);
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
        /** @var Mock|Content */
        $content = Mockery::mock(Content::class)->makePartial();
        $content->shouldReceive('appendItemSections')->once()->with('controller', 'CONTROLLERS');
        $content->shouldReceive('appendItemSections')->once()->with('cpu', 'CPUS');
        $content->shouldReceive('appendItemSections')->once()->with('filesystem', 'DRIVES');
        $content->shouldReceive('appendItemSections')->once()->with('inputdevice', 'INPUTS');
        $content->shouldReceive('appendItemSections')->once()->with('memoryslot', 'MEMORIES');
        $content->shouldReceive('appendItemSections')->once()->with('modem', 'MODEMS');
        $content->shouldReceive('appendItemSections')->once()->with('display', 'MONITORS');
        $content->shouldReceive('appendItemSections')->once()->with('networkinterface', 'NETWORKS');
        $content->shouldReceive('appendItemSections')->once()->with('msofficeproduct', 'OFFICEPACK');
        $content->shouldReceive('appendItemSections')->once()->with('port', 'PORTS');
        $content->shouldReceive('appendItemSections')->once()->with('printer', 'PRINTERS');
        $content->shouldReceive('appendItemSections')->once()->with('registrydata', 'REGISTRY');
        $content->shouldReceive('appendItemSections')->once()->with('sim', 'SIM');
        $content->shouldReceive('appendItemSections')->once()->with('extensionslot', 'SLOTS');
        $content->shouldReceive('appendItemSections')->once()->with('software', 'SOFTWARES');
        $content->shouldReceive('appendItemSections')->once()->with('audiodevice', 'SOUNDS');
        $content->shouldReceive('appendItemSections')->once()->with('storagedevice', 'STORAGES');
        $content->shouldReceive('appendItemSections')->once()->with('displaycontroller', 'VIDEOS');
        $content->shouldReceive('appendItemSections')->once()->with('virtualmachine', 'VIRTUALMACHINES');

        $content->appendAllItemSections();
    }

    public function testAppendItemSections()
    {
        $itemHydrated1 = new ArrayObject(['keyHydrated1' => 'valueHydrated1']);
        $itemHydrated2 = new ArrayObject(['keyHydrated2' => 'valueHydrated2']);
        $itemExtracted1 = ['keyExtracted1' => 'valueExtracted1'];
        $itemExtracted2 = ['keyExtracted2' => 'valueExtracted2'];

        /** @var MockObject|ItemManager */
        $itemManager = $this->createMock(ItemManager::class);
        $itemManager->method('getTableName')->with('type')->willReturn('Table');

        /** @var MockObject|HydratorInterface */
        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')->willReturnMap([
            [$itemHydrated1, $itemExtracted1],
            [$itemHydrated2, $itemExtracted2],
        ]);

        $exporter = $this->createMock(Exporter::class);
        $exporter->method('getHydrator')->with('Table')->willReturn($hydrator);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            [ItemManager::class, $itemManager],
            [Exporter::class, $exporter],
        ]);

        $items = new ResultSet();
        $items->initialize([$itemHydrated1, $itemHydrated2]);

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('type', 'id', 'asc')->willReturn($items);

        /** @var Mock|Content */
        $content = Mockery::mock(Content::class, [$container])->makePartial();
        $content->shouldReceive('appendSection')->once()->with('section', $itemExtracted1);
        $content->shouldReceive('appendSection')->once()->with('section', $itemExtracted2);

        $content->setClient($client);
        $content->appendItemSections('type', 'section');
    }

    public function testAppendSection()
    {
        $items = [
            'key' => 'value',
            'ignored1' => '',
            'ignored2' => null,
            'entity' => '&',
        ];

        $content = new Content($this->createStub(ContainerInterface::class));
        $document = new Document();
        $document->createRoot('root');
        $document->appendChild($content);

        $content->appendSection('section', $items);

        $this->assertXmlStringEqualsXmlString(
            '<CONTENT><section><key>value</key><entity>&amp;</entity></section></CONTENT>',
            $content->dump()
        );
    }
}
