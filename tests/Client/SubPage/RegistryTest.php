<?php

namespace Braintacle\Test\Client\SubPage;

use ArrayIterator;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\SubPage\Registry;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Client\Item\RegistryData;
use Model\Registry\RegistryManager;
use Model\Registry\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Registry::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class RegistryTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXPath(array $registryData): DOMXPath
    {
        $routeArguments = ['id' => 42];

        $client = $this->createMock(Client::class);
        $client->id = 42;
        $client->name = 'name';
        $client->method('getItems')->with('RegistryData', null, null, [])->willReturn(new ArrayIterator($registryData));

        $requestParameters = new ClientRequestParameters();
        $requestParameters->client = $client;

        $definition = new Value();
        $definition->name = '_name';
        $definition->rootKey = Value::HKEY_LOCAL_MACHINE;
        $definition->subKeys = 'sub';
        $definition->value = 'value';

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($routeArguments)->willReturn($requestParameters);

        $registryManager = $this->createStub(RegistryManager::class);
        $registryManager->method('getValueDefinitions')->willReturn(new ArrayIterator([$definition]));

        $handler = new Registry(
            $this->response,
            $routeHelper,
            $dataProcessor,
            $registryManager,
            $this->createTemplateEngine(),
        );
        $response = $handler->handle($this->request);
        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testRegistryActionNoValues()
    {
        $xPath = $this->getXPath([]);
        $this->assertNotXpathMatches($xPath, '//h2');
        $this->assertNotXpathMatches($xPath, '//table');
        $this->assertXpathMatches($xPath, '//p/a[@href="preferencesRegistryValuesPage/?"]');
    }

    public function testRegistryActionWithValues()
    {
        $registryData = new RegistryData();
        $registryData->Value = '_name';
        $registryData->data = '_data';

        $xPath = $this->getXPath([$registryData]);

        $this->assertXpathMatches($xPath, '//h2');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[1][text()="_Value"]');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[2][text()="_Content"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[1][text()="_name"][@title="HKEY_LOCAL_MACHINE\sub\value"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[2][text()="_data"]');
        $this->assertXpathMatches($xPath, '//p/a[@href="preferencesRegistryValuesPage/?"]');
    }
}
