<?php

namespace Braintacle\Test\Client\SubPage;

use ArrayIterator;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\SubPage\Display;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Client\Item\Display as ItemDisplay;
use Model\Client\Item\DisplayController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Display::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class DisplayTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getResponseXpath(array $controllers, array $displays): DOMXPath
    {
        $routeArguments = ['id' => '42'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = $this->createStub(Client::class);
        $client->id = 42;
        $client->name = '_name';
        $client->method('getItems')->willReturnMap([
            ['displayController', null, null, [], new ArrayIterator($controllers)],
            ['display', null, null, [], new ArrayIterator($displays)],
        ]);

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($clientRequestParameters);

        $templateEngine = $this->createTemplateEngine();

        $handler = new Display($this->response, $routeHelper, $dataProcessor, $templateEngine);
        $response = $handler->handle($this->request);

        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testControllers()
    {
        $controller = $this->createStub(DisplayController::class);
        $controller->method('__get')->willReturnMap([
            ['name', 'name1'],
            ['chipset', 'chipset1'],
            ['memory', 1],
            ['currentResolution', 'resolution1'],
        ]);

        $xPath = $this->getResponseXpath([$controller], []);

        $this->assertXpathCount(1, $xPath, '//h2');
        $this->assertXpathMatches($xPath, '//h2[text()="_Display controllers"]');

        $this->assertXpathCount(1, $xPath, '//table');
        $this->assertXpathCount(2, $xPath, '//table/tr');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[1][text()="name1"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[2][text()="chipset1"]');
        $this->assertXpathMatches($xPath, "//table/tr[2]/td[3][text()='1\xC2\xA0MB']");
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[4][text()="resolution1"]');
    }

    public function testNoDisplays()
    {
        $xPath = $this->getResponseXpath([], []);

        $this->assertXpathCount(1, $xPath, '//h2');
        $this->assertXpathMatches($xPath, '//h2[text()="_Display controllers"]');
        $this->assertXpathCount(1, $xPath, '//table');
    }

    public function testDisplays()
    {
        $display = $this->createStub(ItemDisplay::class);
        $display->method('__get')->willReturnMap([
            ['manufacturer', '_manufacturer'],
            ['description', '_description'],
            ['serial', '_serial'],
            ['edid', '_edid'],
            ['type', '_type'],
        ]);

        $xPath = $this->getResponseXpath([], [$display]);

        $this->assertXpathCount(2, $xPath, '//h2');
        $this->assertXpathMatches($xPath, '//h2[text()="_Displays"]');

        $this->assertXpathCount(2, $xPath, '//table');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[1][text()="_manufacturer"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[2][text()="_description"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[3][text()="_serial"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[4][text()="_edid"]');
        $this->assertXpathMatches($xPath, '//table[2]/tr[2]/td[5][text()="_type"]');
    }
}
