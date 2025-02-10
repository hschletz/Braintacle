<?php

namespace Braintacle\Test\Client\SubPage;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\SubPage\Bios;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bios::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class BiosTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getResponseXpath(client $client): DOMXPath
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

        $handler = new Bios($this->response, $routeHelper, $dataProcessor, $templateEngine);
        $response = $handler->handle($this->request);

        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }
    public function testHandler()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'test';
        $client->windows = null;
        $client->biosManufacturer = 'manufacturer';
        $client->biosDate = 'date';
        $client->biosVersion = 'version';

        $xPath = $this->getResponseXpath($client);
        $query = '//table/tr/td[text()="%s"]/following::td[1][text()="%s"]';

        $this->assertXpathMatches($xPath, sprintf($query, '_Manufacturer', 'manufacturer'));
        $this->assertXpathMatches($xPath, sprintf($query, '_Date', 'date'));
        $this->assertXpathMatches($xPath, sprintf($query, '_Version', 'version'));
    }
}
