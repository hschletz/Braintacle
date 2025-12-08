<?php

namespace Braintacle\Test\Client\SubPage;

use ArrayIterator;
use Braintacle\Client\ClientDetails;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\OsType;
use Braintacle\Client\SubPage\Storage;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTimeImmutable;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Client\Item\Filesystem;
use Model\Client\Item\StorageDevice;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Storage::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class StorageTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getResponseXpath(array $devices, array $filesystems, OsType $os): DOMXPath
    {
        $routeArguments = ['id' => '42'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = $this->createStub(Client::class);
        $client->id = 42;
        $client->name = '_name';
        $client->method('getItems')->willReturnMap([
            ['storageDevice', null, null, [], new ArrayIterator($devices)],
            ['filesystem', null, null, [], new ArrayIterator($filesystems)],
        ]);

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($clientRequestParameters);

        $clientDetails = $this->createMock(ClientDetails::class);
        $clientDetails->method('getOsType')->with($client)->willReturn($os);

        $templateEngine = $this->createTemplateEngine();

        $handler = new Storage($this->response, $routeHelper, $dataProcessor, $clientDetails, $templateEngine);
        $response = $handler->handle($this->request);

        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testWindows()
    {
        $device1 = $this->createStub(StorageDevice::class);
        $device1->method('__get')->willReturnMap([
            ['type', 'Fixed hard disk media'], // translated
            ['productName', 'product name 1'],
            ['size', 1024],
            ['device', 'device 1'],
            ['serial', 'serial 1'],
            ['firmware', 'firmware 1'],
        ]);

        $device2 = $this->createStub(StorageDevice::class);
        $device2->method('__get')->willReturnMap([
            ['type', 'External hard disk media'], // translated
            ['productName', 'product name 2'],
            ['size', 2048],
            ['device', 'device 2'],
            ['serial', 'serial 2'],
            ['firmware', 'firmware 2'],
        ]);

        $device3 = $this->createStub(StorageDevice::class);
        $device3->method('__get')->willReturnMap([
            ['type', 'DVD Writer'], // translated
            ['productName', 'product name 3'],
            ['size', 4096],
            ['device', 'device 3'],
            ['serial', 'serial 3'],
            ['firmware', 'firmware 3'],
        ]);

        $device4 = $this->createStub(StorageDevice::class);
        $device4->method('__get')->willReturnMap([
            ['type', 'other'], // not translated
            ['productName', 'product name 4'],
            ['size', 8192],
            ['device', 'device 4'],
            ['serial', 'serial 4'],
            ['firmware', 'firmware 4'],
        ]);

        $filesystem1 = $this->createStub(Filesystem::class);
        $filesystem1->method('__get')->willReturnMap([
            ['letter', 'C:'],
            ['label', 'label1'],
            ['type', 'Hard Drive'], // translated
            ['filesystem', 'filesystem1'],
            ['size', 10000],
            ['usedSpace', 6000],
            ['freeSpace', 4000],
        ]);

        $filesystem2 = $this->createStub(Filesystem::class);
        $filesystem2->method('__get')->willReturnMap([
            ['letter', 'D:'],
            ['label', 'label2'],
            ['type', 'Network Drive'], // translated
            ['filesystem', 'filesystem2'],
            ['size', 10000],
            ['usedSpace', 6000],
            ['freeSpace', 4000],
        ]);

        $filesystem3 = $this->createStub(Filesystem::class);
        $filesystem3->method('__get')->willReturnMap([
            ['letter', 'E:'],
            ['label', 'label3'],
            ['type', 'Removable Drive'], // translated
            ['filesystem', 'filesystem3'],
            ['size', 10000],
            ['usedSpace', 6000],
            ['freeSpace', 4000],
        ]);

        $filesystem4 = $this->createStub(Filesystem::class);
        $filesystem4->method('__get')->willReturnMap([
            ['letter', 'F:'],
            ['label', 'label4'],
            ['type', 'CD-Rom Drive'], // translated
            ['filesystem', 'filesystem4'],
            ['size', 10000],
            ['usedSpace', 6000],
            ['freeSpace', 4000],
        ]);

        $filesystem5 = $this->createStub(Filesystem::class);
        $filesystem5->method('__get')->willReturnMap([
            ['letter', 'G:'],
            ['label', 'label5'],
            ['type', 'other'], // not translated
            ['filesystem', 'filesystem5'],
            ['size', 0], // ignored
            ['usedSpace', 0], // ignored
            ['freeSpace', 0], // ignored
        ]);

        $xPath = $this->getResponseXpath(
            [$device1, $device2, $device3, $device4],
            [$filesystem1, $filesystem2, $filesystem3, $filesystem4, $filesystem5],
            OsType::Windows,
        );

        // Devices
        $this->assertXpathCount(5, $xPath, '//table[1]//th');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[1][text()="_Model"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[1][text()="product name 1"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[2][text()="_Type"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[2][normalize-space(text())="_Hard disk"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[3]/td[2][normalize-space(text())="_External media"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[4]/td[2][normalize-space(text())="_DVD writer"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[5]/td[2][normalize-space(text())="other"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[3][text()="_Size"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[3][normalize-space(text())="1 GB"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[4][text()="_Serial number"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[4][text()="serial 1"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[5][text()="_Firmware version"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[5][text()="firmware 1"]');

        // Filesystems
        $this->assertXpathCount(7, $xPath, '//table[2]//th');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[1][text()="_Letter"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[1][text()="C:"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[2][text()="_Label"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[2][text()="label1"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[3][text()="_Type"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[3][normalize-space(text())="_Partition"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[3]/td[3][normalize-space(text())="_Network"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[4]/td[3][normalize-space(text())="_Removable"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[5]/td[3][normalize-space(text())="_Optical"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[6]/td[3][normalize-space(text())="other"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[4][text()="_Filesystem"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[4][text()="filesystem1"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[5][text()="_Size"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[5][normalize-space(text())="9,77 GB"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[6]/td[5][normalize-space(text())=""]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[6][text()="_Used space"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[6][normalize-space(text())="5,86 GB (60%)"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[6]/td[6][normalize-space(text())=""]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[7][text()="_Free space"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[7][normalize-space(text())="3,91 GB (40%)"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[6]/td[7][normalize-space(text())=""]');
    }

    public function testUnix()
    {
        $device = $this->createStub(StorageDevice::class);
        $device->method('__get')->willReturnMap([
            ['productFamily', 'product family'],
            ['productName', 'product name'],
            ['size', 1024],
            ['device', 'device'],
            ['serial', 'serial'],
            ['firmware', 'firmware'],
        ]);

        $filesystem1 = $this->createStub(Filesystem::class);
        $filesystem1->method('__get')->willReturnMap([
            ['mountpoint', 'mountpoint1'],
            ['device', 'device1'],
            ['filesystem', 'filesystem1'],
            ['creationDate', new DateTimeImmutable('2025-12-07')],
            ['size', 10000],
            ['usedSpace', 6000],
            ['freeSpace', 4000],
        ]);

        $filesystem2 = $this->createStub(Filesystem::class);
        $filesystem2->method('__get')->willReturnMap([
            ['mountpoint', 'mountpoint2'],
            ['device', 'device2'],
            ['filesystem', 'filesystem2'],
            ['creationDate', null],
            ['size', 0],
            ['usedSpace', 0],
            ['freeSpace', 0],
        ]);

        $xPath = $this->getResponseXpath([$device], [$filesystem1, $filesystem2], OsType::Unix);

        // Devices
        $this->assertXpathCount(6, $xPath, '//table[1]//th');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[1][text()="_Manufacturer"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[1][text()="product family"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[2][text()="_Model"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[2][text()="product name"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[3][text()="_Size"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[3][normalize-space(text())="1 GB"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[4][text()="_Device"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[4][normalize-space(text())="device"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[5][text()="_Serial number"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[5][text()="serial"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[6][text()="_Firmware version"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[6][text()="firmware"]');

        // Filesystems
        $this->assertXpathCount(7, $xPath, '//table[2]//th');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[1][text()="_Mountpoint"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[1][text()="mountpoint1"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[2][text()="_Device"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[2][text()="device1"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[3][text()="_Filesystem"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[3][text()="filesystem1"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[4][text()="_Creation date"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[4][text()="07.12.2025"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[3]/td[4][normalize-space(text())=""]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[5][text()="_Size"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[5][normalize-space(text())="9,77 GB"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[3]/td[5][normalize-space(text())=""]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[6][text()="_Used space"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[6][normalize-space(text())="5,86 GB (60%)"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[3]/td[6][normalize-space(text())=""]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[7][text()="_Free space"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[7][normalize-space(text())="3,91 GB (40%)"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[3]/td[7][normalize-space(text())=""]');
    }

    public function testAndroid()
    {
        $device = $this->createStub(StorageDevice::class);
        $device->method('__get')->willReturnMap([
            ['type', '_type'],
            ['size', 1024],
        ]);

        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('__get')->willReturnMap([
            ['mountpoint', '_mountpoint'],
            ['device', '_device'],
            ['filesystem', '_filesystem'],
            ['size', 10000],
            ['usedSpace', 6000],
            ['freeSpace', 4000],
        ]);

        $xPath = $this->getResponseXpath([$device], [$filesystem], OsType::Android);

        // Devices
        $this->assertXpathCount(2, $xPath, '//table[1]//th');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[1][text()="_Type"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[1][normalize-space(text())="_type"]');

        $this->assertXpathMatches($xPath, '//table[1]/tr[1]/th[2][text()="_Size"]');
        $this->assertXpathMatches($xPath, '//table[1]/tr[2]/td[2][normalize-space(text())="1 GB"]');

        // Filesystems
        $this->assertXpathCount(6, $xPath, '//table[2]//th');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[1][text()="_Mountpoint"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[1][text()="_mountpoint"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[2][text()="_Device"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[2][text()="_device"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[3][text()="_Filesystem"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[3][text()="_filesystem"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[4][text()="_Size"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[4][normalize-space(text())="9,77 GB"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[5][text()="_Used space"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[5][normalize-space(text())="5,86 GB (60%)"]');

        $this->assertXpathMatches($xPath, '//table[2]/tr[1]/th[6][text()="_Free space"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[6][normalize-space(text())="3,91 GB (40%)"]');
    }
}
