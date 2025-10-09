<?php

namespace Braintacle\Test\Group\Configuration;

use Braintacle\Configuration\ClientConfig;
use Braintacle\Group\Configuration\ShowConfigurationHandler;
use Braintacle\Group\Group;
use Braintacle\Group\GroupRequestParameters;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Formotron\DataProcessor;
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

    public function handleRequest(?array $values = null): DOMXPath
    {
        $queryParams = ['name' => 'test'];

        $group = $this->createStub(Group::class);
        $group->name = 'test';

        $groupRequestParameters = new GroupRequestParameters();
        $groupRequestParameters->group = $group;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($queryParams, GroupRequestParameters::class)
            ->willReturn($groupRequestParameters);

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->method('getOptions')->with($group)->willReturn($values ?? [
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
        ]);
        $clientConfig->method('getGlobalDefaults')->willReturn([
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
        ]);

        $templateEngine = $this->createTemplateEngine();

        $handler = new ShowConfigurationHandler(
            $this->response,
            $dataProcessor,
            $clientConfig,
            $templateEngine,
        );

        $response = $handler->handle($this->request->withQueryParams($queryParams));
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

    public function testDefaultValues()
    {
        $xPath = $this->handleRequest();
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="contactInterval"]/following-sibling::*[normalize-space(text())="(_Default: 1)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="inventoryInterval"]/following-sibling::*[normalize-space(text())="(_Default: -1)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="packageDeployment"]/following-sibling::*[normalize-space(text())="(_Default: _Yes)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadPeriodDelay"]/following-sibling::*[normalize-space(text())="(_Default: 2)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadCycleDelay"]/following-sibling::*[normalize-space(text())="(_Default: 3)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadFragmentDelay"]/following-sibling::*[normalize-space(text())="(_Default: 4)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadMaxPriority"]/following-sibling::*[normalize-space(text())="(_Default: 5)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="downloadTimeout"]/following-sibling::*[normalize-space(text())="(_Default: 6)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="allowScan"]/following-sibling::*[normalize-space(text())="(_Default: _Yes)"]',
        );
        $this->assertXpathMatches(
            $xPath,
            '//input[@name="scanSnmp"]/following-sibling::*[normalize-space(text())="(_Default: _No)"]',
        );
    }
}
