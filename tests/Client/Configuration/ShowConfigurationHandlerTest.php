<?php

namespace Braintacle\Test\Client\Configuration;

use Braintacle\Client\ClientDetails;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Configuration\ShowConfigurationHandler;
use Braintacle\Configuration\ClientConfig;
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

#[CoversClass(ShowConfigurationHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
final class ShowConfigurationHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    public function handleRequest(?array $values = null, array $networks = []): DOMXPath
    {
        $routeArguments = ['id' => '42'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = $this->createStub(Client::class);
        $client->id = 42;
        $client->name = 'clientName';

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($clientRequestParameters);

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->method('getOptions')->with($client)->willReturn($values ?? [
            'contactInterval' => null,
            'inventoryInterval' => null,
            'packageDeployment' => false,
            'downloadPeriodDelay' => null,
            'downloadCycleDelay' => null,
            'downloadFragmentDelay' => null,
            'downloadMaxPriority' => null,
            'downloadTimeout' => null,
            'allowScan' =>  false,
            'scanSnmp' => false,
            'scanThisNetwork' => null,
        ]);
        $clientConfig->method('getDefaults')->with($client)->willReturn([
            'contactInterval' => 1,
            'inventoryInterval' => -1,
            'packageDeployment' => true,
            'downloadPeriodDelay' => 2,
            'downloadCycleDelay' => 3,
            'downloadFragmentDelay' => 4,
            'downloadMaxPriority' => 5,
            'downloadTimeout' => 6,
            'allowScan' =>  true,
            'scanSnmp' => false,
            'scanThisNetwork' => null,
        ]);
        $clientConfig->method('getEffectiveConfig')->with($client)->willReturn([
            'contactInterval' => 10,
            'inventoryInterval' => 9,
            'packageDeployment' => true,
            'downloadPeriodDelay' => 8,
            'downloadCycleDelay' => 7,
            'downloadFragmentDelay' => 6,
            'downloadMaxPriority' => 5,
            'downloadTimeout' => 4,
            'allowScan' =>  true,
            'scanSnmp' => false,
            'scanThisNetwork' => null,
        ]);

        $clientDetails = $this->createMock(ClientDetails::class);
        $clientDetails->method('getNetworks')->with($client)->willReturn($networks);

        $templateEngine = $this->createTemplateEngine();

        $handler = new ShowConfigurationHandler(
            $this->response,
            $routeHelper,
            $dataProcessor,
            $clientConfig,
            $clientDetails,
            $templateEngine,
        );

        $response = $handler->handle($this->request);
        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testCsrfToken()
    {
        $xPath = $this->handleRequest();
        $this->assertXpathMatches($xPath, '//input[@type="hidden"][@value="csrf_token"]');
    }

    public function testElementRanges()
    {
        $xPath = $this->handleRequest();
        $this->assertXpathMatches($xPath, '//input[@name="contactInterval"][@min="1"][not(@max)]');
        $this->assertXpathMatches($xPath, '//input[@name="inventoryInterval"][@min="-1"][not(@max)]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadPeriodDelay"][@min="1"][not(@max)]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadCycleDelay"][@min="1"][not(@max)]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadFragmentDelay"][@min="1"][not(@max)]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadMaxPriority"][@min="0"][@max="10"]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadTimeout"][@min="1"][not(@max)]');
    }

    public function testElementValuesDefault()
    {
        $xPath = $this->handleRequest();
        $this->assertXpathMatches($xPath, '//input[@name="contactInterval"][@value=""]');
        $this->assertXpathMatches($xPath, '//input[@name="inventoryInterval"][@value=""]');
        $this->assertXpathMatches($xPath, '//input[@name="packageDeployment"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadPeriodDelay"][@value=""]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadCycleDelay"][@value=""]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadFragmentDelay"][@value=""]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadMaxPriority"][@value=""]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadTimeout"][@value=""]');
        $this->assertXpathMatches($xPath, '//input[@name="allowScan"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@name="scanSnmp"][not(@checked)]');
    }

    public function testElementValues()
    {
        $xPath = $this->handleRequest([
            'contactInterval' => 1,
            'inventoryInterval' => -1,
            'packageDeployment' => true,
            'downloadPeriodDelay' => 2,
            'downloadCycleDelay' => 3,
            'downloadFragmentDelay' => 4,
            'downloadMaxPriority' => 5,
            'downloadTimeout' => 6,
            'allowScan' =>  true,
            'scanSnmp' => true,
            'scanThisNetwork' => null,
        ]);
        $this->assertXpathMatches($xPath, '//input[@name="contactInterval"][@value="1"]');
        $this->assertXpathMatches($xPath, '//input[@name="inventoryInterval"][@value="-1"]');
        $this->assertXpathMatches($xPath, '//input[@name="packageDeployment"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadPeriodDelay"][@value="2"]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadCycleDelay"][@value="3"]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadFragmentDelay"][@value="4"]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadMaxPriority"][@value="5"]');
        $this->assertXpathMatches($xPath, '//input[@name="downloadTimeout"][@value="6"]');
        $this->assertXpathMatches($xPath, '//input[@name="allowScan"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@name="scanSnmp"][@checked]');
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testDefaultAndEffectiveValues()
    {
        $xPath = $this->handleRequest();
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="contactInterval"]/following-sibling::*[normalize-space(text())="(_Default: 1, _Effective: 10)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="inventoryInterval"]/following-sibling::*[normalize-space(text())="(_Default: -1, _Effective: 9)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="packageDeployment"]/following-sibling::*[normalize-space(text())="(_Default: _Yes, _Effective: _Yes)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadPeriodDelay"]/following-sibling::*[normalize-space(text())="(_Default: 2, _Effective: 8)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadCycleDelay"]/following-sibling::*[normalize-space(text())="(_Default: 3, _Effective: 7)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadFragmentDelay"]/following-sibling::*[normalize-space(text())="(_Default: 4, _Effective: 6)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadMaxPriority"]/following-sibling::*[normalize-space(text())="(_Default: 5, _Effective: 5)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadTimeout"]/following-sibling::*[normalize-space(text())="(_Default: 6, _Effective: 4)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="allowScan"]/following-sibling::*[normalize-space(text())="(_Default: _Yes, _Effective: _Yes)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="scanSnmp"]/following-sibling::*[normalize-space(text())="(_Default: _No, _Effective: _No)"]',
        );
    }

    public function testNetworksEmpty()
    {
        $xPath = $this->handleRequest();
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"][@disabled]');
        $this->assertXpathCount(1, $xPath, '//select[@name="scanThisNetwork"]/option');
    }

    public function testNetworksNoSelection()
    {
        $xPath = $this->handleRequest(networks: ['192.0.2.0', '192.0.2.1']);
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"][not(@disabled)]');
        $this->assertXpathCount(3, $xPath, '//select[@name="scanThisNetwork"]/option');
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"]/option[1][not(text())]');
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"]/option[2][text()="192.0.2.0"][not(@selected)]');
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"]/option[3][text()="192.0.2.1"][not(@selected)]');
    }

    public function testNetworksSelected()
    {
        $xPath = $this->handleRequest(
            values: [
                'contactInterval' => null,
                'inventoryInterval' => null,
                'packageDeployment' => false,
                'downloadPeriodDelay' => null,
                'downloadCycleDelay' => null,
                'downloadFragmentDelay' => null,
                'downloadMaxPriority' => null,
                'downloadTimeout' => null,
                'allowScan' =>  false,
                'scanSnmp' => false,
                'scanThisNetwork' => '192.0.2.0',
            ],
            networks: ['192.0.2.0', '192.0.2.1'],
        );
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"][not(@disabled)]');
        $this->assertXpathCount(3, $xPath, '//select[@name="scanThisNetwork"]/option');
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"]/option[1][not(text())][not(@selected)]');
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"]/option[2][text()="192.0.2.0"][@selected]');
        $this->assertXpathMatches($xPath, '//select[@name="scanThisNetwork"]/option[3][text()="192.0.2.1"][not(@selected)]');
    }
}
