<?php

namespace Braintacle\Test\Client\SubPage;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\SubPage\General;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(General::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class GeneralTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getResponseXpath(Client $client): DOMXPath
    {
        $routeArguments = ['id' => '42'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($clientRequestParameters);

        $templateEngine = $this->createTemplateEngine();

        $handler = new General($this->response, $routeHelper, $dataProcessor, $templateEngine);
        $response = $handler->handle($this->request);

        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testStandardData()
    {
        $client = $this->createStub(Client::class);
        $client->id = 42;
        $client->name = 'name';
        $client->idString = 'id_string';
        $client->inventoryDate = new DateTime('2025-03-13 11:16:15');
        $client->lastContactDate = new DateTime('2025-03-13 11:17:34');
        $client->userAgent = 'user_agent';
        $client->manufacturer = 'manufacturer';
        $client->productName = 'product_name';
        $client->serial = 'serial';
        $client->assetTag = 'asset_tag';
        $client->type = 'type';
        $client->osName = 'os_name';
        $client->osVersionString = 'os_version_string';
        $client->osVersionNumber = 'os_version_number';
        $client->osComment = 'os_comment';
        $client->cpuType = 'cpu_type';
        $client->cpuClock = 1234;
        $client->cpuCores = 2;
        $client->memorySlot = [(object) ['size' => 2], (object) ['size' => 3]];
        $client->physicalMemory = 1234;
        $client->swapMemory = 5678;
        $client->userName = 'user_name';
        $client->uuid = 'uuid';
        $client->method('offsetGet')->willReturnMap([
            ['IsSerialBlacklisted', false],
            ['IsAssetTagBlacklisted', false],
            ['MemorySlot', [(object) ['size' => 2], (object) ['size' => 3]]],
        ]);

        $xPath = $this->getResponseXpath($client);

        $query = '//table/tr/td[text()="%s"]/following::td[1][normalize-space(text())="%s"]';
        $this->assertXPathMatches($xPath, sprintf($query, '_ID', '42'));
        $this->assertXPathMatches($xPath, sprintf($query, '_ID string', 'id_string'));
        $this->assertXPathMatches(
            $xPath,
            sprintf($query, '_Inventory date', "Donnerstag, 13.\xC2\xA0März 2025 um 11:16:15 MEZ")
        );
        $this->assertXPathMatches(
            $xPath,
            sprintf($query, '_Last contact', "Donnerstag, 13.\xC2\xA0März 2025 um 11:17:34 MEZ")
        );
        $this->assertXPathMatches($xPath, sprintf($query, '_User Agent', 'user_agent'));
        $this->assertXPathMatches($xPath, sprintf($query, '_Model', 'manufacturer product_name'));
        $this->assertXPathMatches($xPath, sprintf($query, '_Serial number', 'serial'));
        $this->assertXPathMatches($xPath, sprintf($query, '_Asset tag', 'asset_tag'));
        $this->assertXPathMatches($xPath, sprintf($query, '_Type', 'type'));
        $this->assertXPathMatches(
            $xPath,
            sprintf($query, '_Operating System', 'os_name os_version_string (os_version_number)')
        );
        $this->assertXPathMatches($xPath, sprintf($query, '_Comment', 'os_comment'));
        $this->assertXPathMatches($xPath, sprintf($query, '_CPU type', 'cpu_type'));
        $this->assertXPathMatches($xPath, sprintf($query, '_CPU clock', "1234\xC2\xA0MHz"));
        $this->assertXPathMatches($xPath, sprintf($query, '_Number of CPU cores', 2));
        $this->assertXPathMatches($xPath, sprintf($query, '_RAM detected by agent', "5\xC2\xA0MB"));
        $this->assertXPathMatches($xPath, sprintf($query, '_RAM reported by OS', "1234\xC2\xA0MB"));
        $this->assertXPathMatches($xPath, sprintf($query, '_Swap memory', "5678\xC2\xA0MB"));
        $this->assertXPathMatches($xPath, sprintf($query, '_Last user logged in', 'user_name'));
        $this->assertXPathMatches($xPath, sprintf($query, '_UUID', 'uuid'));
        $this->assertXPathMatches($xPath, '//table/tr/td[text()="serial"][not(@class)]');
        $this->assertXPathMatches($xPath, '//table/tr/td[text()="asset_tag"][not(@class)]');
    }

    public function testNoUuid()
    {
        $client = $this->createStub(Client::class);
        $client->id = 42;
        $client->name = 'name';
        $client->idString = 'id_string';
        $client->inventoryDate = new DateTime();
        $client->lastContactDate = new DateTime();
        $client->userAgent = 'user_agent';
        $client->manufacturer = 'manufacturer';
        $client->productName = 'product_name';
        $client->serial = 'serial';
        $client->assetTag = 'asset_tag';
        $client->type = 'type';
        $client->osName = 'os_name';
        $client->osVersionString = 'os_version_string';
        $client->osVersionNumber = 'os_version_number';
        $client->osComment = 'os_comment';
        $client->cpuType = 'cpu_type';
        $client->cpuClock = 1234;
        $client->cpuCores = 2;
        $client->physicalMemory = 1234;
        $client->swapMemory = 5678;
        $client->userName = 'user_name';
        $client->uuid = null;
        $client->method('offsetGet')->willReturnMap([
            ['MemorySlot', []],
        ]);

        $xPath = $this->getResponseXpath($client);

        $this->assertNotXpathMatches($xPath, '//td[text()="_UUID"]');
    }

    public function testSerialBlacklisted()
    {
        $client = $this->createStub(Client::class);
        $client->id = 42;
        $client->name = 'test';
        $client->idString = 'id_string';
        $client->inventoryDate = new DateTime();
        $client->lastContactDate = new DateTime();
        $client->userAgent = 'user_agent';
        $client->manufacturer = 'manufacturer';
        $client->productName = 'product_name';
        $client->serial = 'serial';
        $client->assetTag = 'asset_tag';
        $client->type = 'type';
        $client->osName = 'os_name';
        $client->osVersionString = 'os_version_string';
        $client->osVersionNumber = 'os_version_number';
        $client->osComment = 'os_comment';
        $client->cpuType = 'cpu_type';
        $client->cpuClock = 1234;
        $client->cpuCores = 2;
        $client->physicalMemory = 1234;
        $client->swapMemory = 5678;
        $client->userName = 'user_name';
        $client->uuid = 'uuid';
        $client->method('offsetGet')->willReturnMap([
            ['IsSerialBlacklisted', true],
            ['IsAssetTagBlacklisted', false],
            ['MemorySlot', []],
        ]);

        $xPath = $this->getResponseXpath($client);

        $this->assertXPathMatches($xPath, '//td[text()="serial"][@class="blacklisted"]');
        $this->assertXPathMatches($xPath, '//td[text()="asset_tag"][not(@class)]');
    }

    public function testAssetTagBlacklisted()
    {
        $client = $this->createStub(Client::class);
        $client->id = 1;
        $client->name = 'test';
        $client->idString = 'id_string';
        $client->inventoryDate = new DateTime();
        $client->lastContactDate = new DateTime();
        $client->userAgent = 'user_agent';
        $client->manufacturer = 'manufacturer';
        $client->productName = 'product_name';
        $client->serial = 'serial';
        $client->assetTag = 'asset_tag';
        $client->type = 'type';
        $client->osName = 'os_name';
        $client->osVersionString = 'os_version_string';
        $client->osVersionNumber = 'os_version_number';
        $client->osComment = 'os_comment';
        $client->cpuType = 'cpu_type';
        $client->cpuClock = 1234;
        $client->cpuCores = 2;
        $client->physicalMemory = 1234;
        $client->swapMemory = 5678;
        $client->userName = 'user_name';
        $client->uuid = 'uuid';
        $client->method('offsetGet')->willReturnMap([
            ['IsSerialBlacklisted', false],
            ['IsAssetTagBlacklisted', true],
            ['MemorySlot', []],
        ]);

        $xPath = $this->getResponseXpath($client);

        $this->assertXPathMatches($xPath, '//td[text()="serial"][not(@class)]');
        $this->assertXPathMatches($xPath, '//td[text()="asset_tag"][@class="blacklisted"]');
    }

    public function testWindowsUser()
    {
        $client = $this->createMock(Client::class);
        $client->id = 1;
        $client->name = 'test';
        $client->idString = 'id_string';
        $client->inventoryDate = new DateTime();
        $client->lastContactDate = new DateTime();
        $client->userAgent = 'user_agent';
        $client->manufacturer = 'manufacturer';
        $client->productName = 'product_name';
        $client->serial = 'serial';
        $client->assetTag = 'asset_tag';
        $client->type = 'type';
        $client->osName = 'os_name';
        $client->osVersionString = 'os_version_string';
        $client->osVersionNumber = 'os_version_number';
        $client->osComment = 'os_comment';
        $client->cpuType = 'cpu_type';
        $client->cpuClock = 1234;
        $client->cpuCores = 2;
        $client->physicalMemory = 1234;
        $client->swapMemory = 5678;
        $client->userName = 'user_name';
        $client->uuid = 'uuid';
        $client->method('offsetGet')->willReturnMap([
            ['Windows', ['UserDomain' => 'domain']],
            ['MemorySlot', []],
        ]);
        $client->method('offsetExists')->with('Windows')->willReturn(true);

        $xPath = $this->getResponseXpath($client);

        $this->assertXPathMatches($xPath, '//td[text()="user_name @ domain"]');
    }

    public function testWindowsNoArch()
    {
        $client = $this->createMock(Client::class);
        $client->id = 1;
        $client->name = 'test';
        $client->idString = 'id_string';
        $client->inventoryDate = new DateTime();
        $client->lastContactDate = new DateTime();
        $client->userAgent = 'user_agent';
        $client->manufacturer = 'manufacturer';
        $client->productName = 'product_name';
        $client->serial = 'serial';
        $client->assetTag = 'asset_tag';
        $client->type = 'type';
        $client->osName = 'os_name';
        $client->osVersionString = 'os_version_string';
        $client->osVersionNumber = 'os_version_number';
        $client->osComment = 'os_comment';
        $client->cpuType = 'cpu_type';
        $client->cpuClock = 1234;
        $client->cpuCores = 2;
        $client->physicalMemory = 1234;
        $client->swapMemory = 5678;
        $client->userName = 'user_name';
        $client->uuid = 'uuid';
        $client->method('offsetGet')->willReturnMap([
            ['Windows', ['CpuArchitecture' => null, 'UserDomain' => 'domain']],
            ['MemorySlot', []],
        ]);
        $client->method('offsetExists')->with('Windows')->willReturn(true);

        $xPath = $this->getResponseXpath($client);

        $this->assertXPathMatches(
            $xPath,
            '//td[normalize-space(text())="os_name os_version_string (os_version_number)"]'
        );
    }

    public function testWindowsWithArch()
    {
        $client = $this->createMock(Client::class);
        $client->id = 1;
        $client->name = 'test';
        $client->idString = 'id_string';
        $client->inventoryDate = new DateTime();
        $client->lastContactDate = new DateTime();
        $client->userAgent = 'user_agent';
        $client->manufacturer = 'manufacturer';
        $client->productName = 'product_name';
        $client->serial = 'serial';
        $client->assetTag = 'asset_tag';
        $client->type = 'type';
        $client->osName = 'os_name';
        $client->osVersionString = 'os_version_string';
        $client->osVersionNumber = 'os_version_number';
        $client->osComment = 'os_comment';
        $client->cpuType = 'cpu_type';
        $client->cpuClock = 1234;
        $client->cpuCores = 2;
        $client->physicalMemory = 1234;
        $client->swapMemory = 5678;
        $client->userName = 'user_name';
        $client->uuid = 'uuid';
        $client->method('offsetGet')->willReturnMap([
            ['Windows', ['CpuArchitecture' => 'cpu_arch', 'UserDomain' => 'domain']],
            ['MemorySlot', []],
        ]);
        $client->method('offsetExists')->with('Windows')->willReturn(true);

        $xPath = $this->getResponseXpath($client);

        $this->assertXPathMatches(
            $xPath,
            "//td[normalize-space(text())='os_name os_version_string (os_version_number) \xE2\x80\x93 cpu_arch']"
        );
    }
}
