<?php

namespace Braintacle\Test\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Packages\ShowPackagesHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\Function\TranslateFunction;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Package\Assignment;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ShowPackagesHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function handleRequest(
        array $assignedPackages,
        array $assignablePackages,
    ): ResponseInterface {
        $clientId = 42;
        $routeArguments = ['id' => "$clientId"];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = $this->createMock(Client::class);
        $client->id = $clientId;
        $client->name = '<clientName>';
        $client->method('getPackageAssignments')->with('packageName', 'asc')->willReturn($assignedPackages);
        $client->method('getAssignablePackages')->willReturn($assignablePackages);

        $requestData = new ClientRequestParameters();
        $requestData->client = $client;

        $pathForRouteFunction = $this->createMock(PathForRouteFunction::class);
        $pathForRouteFunction->method('__invoke')->willReturnCallback(
            fn ($route, $arguments, $query) => $route . json_encode($arguments) . json_encode($query)
        );

        $translateFunction = $this->createStub(TranslateFunction::class);
        $translateFunction->method('__invoke')->willReturnCallback(fn ($message, ...$args) => '_' . vsprintf($message, $args));

        $templateEngine = $this->createTemplateEngine([
            PathForRouteFunction::class => $pathForRouteFunction,
            TranslateFunction::class => $translateFunction,
        ]);

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($routeArguments, ClientRequestParameters::class)->willReturn($requestData);

        $handler = new ShowPackagesHandler($this->response, $routeHelper, $dataProcessor, $templateEngine);

        return $handler->handle($this->request);
    }

    public function testActiveMenu()
    {
        $response = $this->handleRequest([], []);
        $xPath = $this->getXPathFromMessage($response);
        $this->assertEquals('_Packages', $xPath->evaluate('string(//*[contains(@class, "navigation_details")]/li[@class="active"]/a)'));
    }

    public function testHeading()
    {
        $response = $this->handleRequest([], []);
        $xPath = $this->getXPathFromMessage($response);
        $heading = $xPath->evaluate('string(//h1)');
        $this->assertEquals("_Details for client '<clientName>'", $heading);
    }

    public function testNoPackages()
    {
        $response = $this->handleRequest([], []);
        $xPath = $this->getXPathFromMessage($response);
        $this->assertNotXpathMatches($xPath, '//h2');
    }

    public function testAssignedPackages()
    {
        $package1 = new Assignment();
        $package1->packageName = '<package1>';
        $package1->status = Assignment::PENDING;
        $package1->timestamp = new DateTime('2024-01-11T16:56:52');

        $package2 = new Assignment();
        $package2->packageName = '<package2>';
        $package2->status = Assignment::RUNNING;
        $package2->timestamp = new DateTime('2024-01-12T16:56:52');

        $package3 = new Assignment();
        $package3->packageName = '<package3>';
        $package3->status = Assignment::SUCCESS;
        $package3->timestamp = new DateTime('2024-01-13T16:56:52');

        $package4 = new Assignment();
        $package4->packageName = '<package4>';
        $package4->status = '<ERROR>';
        $package4->timestamp = new DateTime('2024-01-14T16:56:52');

        $response = $this->handleRequest([$package1, $package2, $package3, $package4], []);
        $xPath = $this->getXPathFromMessage($response);

        $headings = $xPath->query('//h2');
        $this->assertCount(1, $headings);
        $this->assertEquals('_Assigned packages', $headings->item(0)->nodeValue);

        $tr = '//table[contains(@class, "assignedPackages")]/tr';
        $this->assertXpathCount(5, $xPath, $tr);

        $this->assertXpathMatches($xPath, $tr . '[2]/td[1][text()="<package1>"]');
        $this->assertXpathMatches($xPath, $tr . '[2]/td[2][text()="_Pending"][@class="package_pending"]');
        $this->assertXpathMatches($xPath, $tr . '[2]/td[3][text()="11.01.24, 16:56"]');
        $this->assertNotXpathMatches($xPath, $tr . '[2]/td[4]/*');
        $this->assertXpathMatches($xPath, $tr . '[2]/td[5]/button[@data-package="<package1>"][@data-action="remove"][normalize-space(text())="_remove"]');

        $this->assertXpathMatches($xPath, $tr . '[3]/td[1][text()="<package2>"]');
        $this->assertXpathMatches($xPath, $tr . '[3]/td[2][text()="_Running"][@class="package_running"]');
        $this->assertXpathMatches($xPath, $tr . '[3]/td[3][text()="12.01.24, 16:56"]');
        $this->assertXpathMatches($xPath, $tr . '[3]/td[4]/button[@data-package="<package2>"][@data-action="reset"][normalize-space(text())="_reset"]');
        $this->assertXpathMatches($xPath, $tr . '[3]/td[5]/button[@data-package="<package2>"][@data-action="remove"][normalize-space(text())="_remove"]');

        $this->assertXpathMatches($xPath, $tr . '[4]/td[1][text()="<package3>"]');
        $this->assertXpathMatches($xPath, $tr . '[4]/td[2][text()="_Success"][@class="package_success"]');
        $this->assertXpathMatches($xPath, $tr . '[4]/td[3][text()="13.01.24, 16:56"]');
        $this->assertXpathMatches($xPath, $tr . '[4]/td[4]/button[@data-package="<package3>"][@data-action="reset"][normalize-space(text())="_reset"]');
        $this->assertXpathMatches($xPath, $tr . '[4]/td[5]/button[@data-package="<package3>"][@data-action="remove"][normalize-space(text())="_remove"]');

        $this->assertXpathMatches($xPath, $tr . '[5]/td[1][text()="<package4>"]');
        $this->assertXpathMatches($xPath, $tr . '[5]/td[2][text()="<ERROR>"][@class="package_error"]');
        $this->assertXpathMatches($xPath, $tr . '[5]/td[3][text()="14.01.24, 16:56"]');
        $this->assertXpathMatches($xPath, $tr . '[5]/td[4]/button[@data-package="<package4>"][@data-action="reset"][normalize-space(text())="_reset"]');
        $this->assertXpathMatches($xPath, $tr . '[5]/td[5]/button[@data-package="<package4>"][@data-action="remove"][normalize-space(text())="_remove"]');
    }

    public function testAssignablePackages()
    {
        $response = $this->handleRequest([], ['<package1>', '<package2>']);
        $xPath = $this->getXPathFromMessage($response);

        $headings = $xPath->query('//h2');
        $this->assertCount(1, $headings);
        $this->assertEquals('_Assign packages', $headings->item(0)->nodeValue);

        $this->assertXpathMatches($xPath, '//form//input[@name="packages[]"][@value="<package1>"]/following-sibling::span[text()="<package1>"]');
        $this->assertXpathMatches($xPath, '//form//input[@name="packages[]"][@value="<package2>"]/following-sibling::span[text()="<package2>"]');
    }
}
