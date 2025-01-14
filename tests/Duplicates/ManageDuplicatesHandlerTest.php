<?php

namespace Braintacle\Test\Duplicates;

use Braintacle\Duplicates\Criterion;
use Braintacle\Duplicates\DuplicatesRequestParameters;
use Braintacle\Duplicates\ManageDuplicatesHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use DOMXPath;
use Formotron\DataProcessor;
use Library\MacAddress;
use Model\Client\Client;
use Model\Client\DuplicatesManager;
use Model\Config;
use PHPUnit\Framework\TestCase;

class ManageDuplicatesHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(bool $optionMerge): DOMXPath
    {
        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn(['id' => '42']);

        $requestParameters = new DuplicatesRequestParameters();
        $requestParameters->criterion = Criterion::Name;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with(['id' => '42', 'criterion' => 'name'])->willReturn($requestParameters);

        $macAddress1 = $this->createStub(MacAddress::class);
        $macAddress1->method('__toString')->willReturn('mac1');

        $client1 = $this->createMock(Client::class);
        $client1->id = 1;
        $client1->name = 'name1';
        $client1->serial = 'serial1';
        $client1->assetTag = null;
        $client1->lastContactDate = new DateTime('2022-12-22T13:22:00');
        $client1->method('offsetGet')->with('NetworkInterface.MacAddress')->willReturn($macAddress1);

        $macAddress2 = $this->createStub(MacAddress::class);
        $macAddress2->method('__toString')->willReturn('mac2');

        $client2 = $this->createMock(Client::class);
        $client2->id = 2;
        $client2->name = 'name2';
        $client2->serial = null;
        $client2->assetTag = 'at2';
        $client2->lastContactDate = new DateTime('2023-01-28T17:27:00');
        $client2->method('offsetGet')->with('NetworkInterface.MacAddress')->willReturn($macAddress2);

        $duplicatesManager = $this->createStub(DuplicatesManager::class);
        $duplicatesManager->method('find')->willReturn([$client1, $client2]);

        $config = $this->createStub(Config::class);
        $config->method('__get')->willReturn($optionMerge ? 1 : 0);

        $pathForRouteFunction = $this->createStub(PathForRouteFunction::class);
        $pathForRouteFunction->method('__invoke')->willReturnCallback(fn ($name, $arguments, $query) => $name . '?' . http_build_query($query));

        $templateEngine = $this->createTemplateEngine([PathForRouteFunction::class => $pathForRouteFunction]);

        $handler = new ManageDuplicatesHandler(
            $this->response,
            $routeHelper,
            $dataProcessor,
            $duplicatesManager,
            $config,
            $templateEngine,
        );
        $response = $handler->handle($this->request->withQueryParams(['criterion' => 'name']));

        return $this->getXPathFromMessage($response);
    }

    public function testTable()
    {
        $xPath = $this->getXpath(false);

        $this->assertXpathCount(2, $xPath, '//tr[td]');

        $this->assertXpathMatches($xPath, '//tr[2]/td[1]/input[@value="1"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[2]/a[@href="showClientCustomFields?id=1"][text()="name1"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[3]/a[@href="duplicatesAllow?criteria=MacAddress&value=mac1"][text()="mac1"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[4]/a[@href="duplicatesAllow?criteria=Serial&value=serial1"][text()="serial1"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[5][not(a)]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[6][normalize-space(text())="22.12.22, 13:22"]');

        $this->assertXpathMatches($xPath, '//tr[3]/td[1]/input[@value="2"]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[2]/a[@href="showClientCustomFields?id=2"][text()="name2"]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[3]/a[@href="duplicatesAllow?criteria=MacAddress&value=mac2"][text()="mac2"]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[4][not(a)]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[5]/a[@href="duplicatesAllow?criteria=AssetTag&value=at2"][text()="at2"]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[6][normalize-space(text())="28.01.23, 17:27"]');
    }

    public function testOptionsUnset()
    {
        $xPath = $this->getXpath(false);

        $this->assertXpathMatches($xPath, '//input[@value="mergeCustomFields"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@value="mergeConfig"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@value="mergeGroups"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@value="mergePackages"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@value="mergeProductKey"][not(@checked)]');
    }

    public function testOptionsSet()
    {
        $xPath = $this->getXpath(true);

        $this->assertXpathMatches($xPath, '//input[@value="mergeCustomFields"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@value="mergeConfig"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@value="mergeGroups"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@value="mergePackages"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@value="mergeProductKey"][@checked]');
    }
}
