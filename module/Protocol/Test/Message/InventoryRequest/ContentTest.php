<?php

/**
 * Tests for Content element
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use ArrayIterator;
use ArrayObject;
use Braintacle\Client\ClientDetails;
use Braintacle\Client\Exporter;
use Braintacle\Dom\DomMapper;
use Braintacle\Dom\Element;
use DOMDocument;
use Laminas\Db\ResultSet\ResultSet;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\ItemManager;
use Protocol\Message\InventoryRequest\Content;
use Laminas\Hydrator\HydratorInterface;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\Attributes\TestWith;
use Protocol\Hydrator\ClientsBios as ClientsBiosHydrator;
use Protocol\Hydrator\ClientsHardware as ClientsHardwareHydrator;
use UnhandledMatchError;

class ContentTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    private function createContent(
        ?ClientDetails $clientDetails = null,
        ?ClientsHardwareHydrator $clientsHardwareHydrator = null,
        ?ClientsBiosHydrator $clientsBiosHydrator = null,
        ?Exporter $exporter = null,
        ?ItemManager $itemManager = null,
    ): Content {
        return new Content(
            $clientDetails ?? $this->createStub(ClientDetails::class),
            $exporter ?? $this->createStub(Exporter::class),
            $clientsHardwareHydrator ?? $this->createStub(ClientsHardwareHydrator::class),
            $clientsBiosHydrator ?? $this->createStub(ClientsBiosHydrator::class),
            $itemManager ?? $this->createStub(ItemManager::class),
        );
    }

    private function createContentPartialMock(
        ?ClientDetails $clientDetails = null,
        ?Exporter $exporter = null,
        ?ClientsHardwareHydrator $clientsHardwareHydrator = null,
        ?ClientsBiosHydrator $clientsBiosHydrator = null,
        ?ItemManager $itemManager = null,
    ): Content|Mock {
        /** @psalm-suppress InvalidArgument (Mockery bug) */
        return Mockery::mock(Content::class, [
            $clientDetails ?? $this->createStub(ClientDetails::class),
            $exporter ?? $this->createStub(Exporter::class),
            $clientsHardwareHydrator ?? $this->createStub(ClientsHardwareHydrator::class),
            $clientsBiosHydrator ?? $this->createStub(ClientsBiosHydrator::class),
            $itemManager ?? $this->createStub(ItemManager::class),
        ])->makePartial();
    }

    public function testConstructor()
    {
        $content = $this->createContent();
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

        $content->appendSections();
    }

    public function testAppendSystemSectionHardware()
    {
        $data = ['NAME' => 'value'];

        $client = $this->createStub(Client::class);

        $hardwareHydrator = $this->createMock(ClientsHardwareHydrator::class);
        $hardwareHydrator->method('extract')->with($client)->willReturn($data);

        $biosHydrator = $this->createMock(ClientsBiosHydrator::class);
        $biosHydrator->expects($this->never())->method('extract');

        $content = $this->createContentPartialMock(
            clientsHardwareHydrator: $hardwareHydrator,
            clientsBiosHydrator: $biosHydrator,
        );
        $content->shouldReceive('appendSection')->once()->with(Content::SYSTEM_SECTION_HARDWARE, $data);

        $content->setClient($client);
        $content->appendSystemSection(Content::SYSTEM_SECTION_HARDWARE);
    }

    public function testAppendSystemSectionBios()
    {
        $data = ['NAME' => 'value'];

        $client = $this->createStub(Client::class);

        $hardwareHydrator = $this->createMock(ClientsHardwareHydrator::class);
        $hardwareHydrator->expects($this->never())->method('extract');

        $biosHydrator = $this->createMock(ClientsBiosHydrator::class);
        $biosHydrator->method('extract')->with($client)->willReturn($data);

        $content = $this->createContentPartialMock(
            clientsHardwareHydrator: $hardwareHydrator,
            clientsBiosHydrator: $biosHydrator,
        );
        $content->shouldReceive('appendSection')->once()->with(Content::SYSTEM_SECTION_BIOS, $data);

        $content->setClient($client);
        $content->appendSystemSection(Content::SYSTEM_SECTION_BIOS);
    }

    public function testAppendSectionsInvalidSection()
    {
        $this->expectException(UnhandledMatchError::class);

        $content = $this->createContent();
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

        $content = $this->createContentPartialMock(exporter: $exporter);
        $content->shouldReceive('appendSection')->once()->with('JAVAINFOS', $data);

        $content->setClient($client);
        $content->appendOsSpecificSection();
    }

    public function testAppendOsSpecificSectionOther()
    {
        $client = $this->createMock(Client::class);
        $client->method('offsetGet')->with('Android')->willReturn(null);

        $content = $this->createContentPartialMock();
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

        $content = $this->createContentPartialMock();
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
        $content = $this->createContentPartialMock();

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

    #[TestWith(['REGISTRY', 'getRegistryData'])]
    public function testAppendItemSectionsWithDomMapper(string $section, string $method)
    {
        $itemExported1 = ['keyExported1' => 'valueExported1'];
        $itemExported2 = ['keyExported2' => 'valueExported2'];

        $item1 = $this->createStub(DomMapper::class);
        $item1->method('exportToDom')->willReturn($itemExported1);

        $item2 = $this->createStub(DomMapper::class);
        $item2->method('exportToDom')->willReturn($itemExported2);

        $client = $this->createStub(Client::class);

        $clientDetails = $this->createMock(ClientDetails::class);
        $clientDetails->method($method)->with($client)->willReturn(new ArrayIterator([$item1, $item2]));

        $content = $this->createContentPartialMock(clientDetails: $clientDetails);
        $content->shouldReceive('appendSection')->once()->with($section, $itemExported1);
        $content->shouldReceive('appendSection')->once()->with($section, $itemExported2);

        $content->setClient($client);
        $content->appendItemSections('type', $section); // first argument is not used with DOM mappers.
    }

    public function testAppendItemSectionsWithLegacyItems()
    {
        $itemHydrated1 = new ArrayObject(['keyHydrated1' => 'valueHydrated1']);
        $itemHydrated2 = new ArrayObject(['keyHydrated2' => 'valueHydrated2']);
        $itemExtracted1 = ['keyExtracted1' => 'valueExtracted1'];
        $itemExtracted2 = ['keyExtracted2' => 'valueExtracted2'];

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->method('extract')->willReturnMap([
            [$itemHydrated1, $itemExtracted1],
            [$itemHydrated2, $itemExtracted2],
        ]);

        $exporter = $this->createMock(Exporter::class);
        $exporter->method('getHydrator')->with('Table')->willReturn($hydrator);

        $itemManager = $this->createMock(ItemManager::class);
        $itemManager->method('getTableName')->with('type')->willReturn('Table');

        $items = new ResultSet();
        $items->initialize([$itemHydrated1, $itemHydrated2]);

        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('type', 'id', 'asc')->willReturn($items);

        $content = $this->createContentPartialMock(exporter: $exporter, itemManager: $itemManager);
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

        $content = $this->createContent();
        $document = new DOMDocument();
        $document->appendChild($content);

        $content->appendSection('section', $items);

        $this->assertXmlStringEqualsXmlString(
            '<CONTENT><section><key>value</key><entity>&amp;</entity></section></CONTENT>',
            $document->saveXML()
        );
    }
}
