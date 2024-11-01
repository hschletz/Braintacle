<?php

namespace Braintacle\Test\Group\Packages;

use Braintacle\Group\GroupRequestParameters;
use Braintacle\Group\Packages\ShowPackagesHandler;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Test\HttpHandlerTestTrait;
use Console\Template\Functions\TranslateFunction;
use Formotron\FormProcessor;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ShowPackagesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    private function handleRequest(
        array $assignedPackages,
        array $assignablePackages,
    ): ResponseInterface {
        $groupName = '<groupName>';
        $queryParams = ['group' => $groupName];

        $group = $this->createMock(Group::class);
        $group->method('__get')->with('name')->willReturn($groupName);
        $group->method('getPackages')->with('asc')->willReturn($assignedPackages);
        $group->method('getAssignablePackages')->willReturn($assignablePackages);

        $formData = new GroupRequestParameters();
        $formData->group = $group;

        $pathForRouteFunction = $this->createStub(PathForRouteFunction::class);
        $pathForRouteFunction->method('__invoke')->willReturnCallback(
            fn ($route, $routeArguments, $queryParams) => '/' . $route . '?' . http_build_query($queryParams)
        );
        $translateFunction = $this->createStub(TranslateFunction::class);
        $translateFunction->method('__invoke')->willReturnCallback(fn ($message, ...$args) => '_' . vsprintf($message, $args));
        $templateEngine = $this->createTemplateEngine([
            PathForRouteFunction::class => $pathForRouteFunction,
            TranslateFunction::class => $translateFunction,
        ]);

        $formProcessor = $this->createMock(FormProcessor::class);
        $formProcessor->method('process')->with($queryParams, GroupRequestParameters::class)->willReturn($formData);

        $handler = new ShowPackagesHandler($this->response, $formProcessor, $templateEngine);
        $request = $this->request->withQueryParams($queryParams);

        return $handler->handle($request);
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
        $this->assertEquals("_Details for group '<groupName>'", $heading);
    }

    public function testNoPackages()
    {
        $response = $this->handleRequest([], []);
        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(0, $xPath->query('//h2'));
    }

    public function testAssignedPackages()
    {
        $response = $this->handleRequest(['package1', 'package2'], []);
        $xPath = $this->getXPathFromMessage($response);

        $headings = $xPath->query('//h2');
        $this->assertCount(1, $headings);
        $this->assertEquals('_Assigned packages', $headings->item(0)->nodeValue);

        $tr = '//table[contains(@class, "assignedPackages")]/tr';
        $this->assertCount(2, $xPath->query($tr));
        $this->assertCount(1, $xPath->query($tr . '[1]/td[1][text()="package1"]'));
        $this->assertCount(1, $xPath->query($tr . '[1]/td[2]/button[@data-package="package1"][normalize-space(text())="_remove"]'));
        $this->assertCount(1, $xPath->query($tr . '[2]/td[1][text()="package2"]'));
        $this->assertCount(1, $xPath->query($tr . '[2]/td[2]/button[@data-package="package2"][normalize-space(text())="_remove"]'));
    }

    public function testAssignablePackages()
    {
        $response = $this->handleRequest([], ['package1', 'package2']);
        $xPath = $this->getXPathFromMessage($response);

        $headings = $xPath->query('//h2');
        $this->assertCount(1, $headings);
        $this->assertEquals('_Assign packages', $headings->item(0)->nodeValue);

        $this->assertCount(1, $xPath->query('//form[@action="/assignPackageToGroup?name=%3CgroupName%3E"]'));
        $this->assertCount(1, $xPath->query('//form//input[@name="packages[]"][@value="package1"]/following-sibling::span[text()="package1"]'));
        $this->assertCount(1, $xPath->query('//form//input[@name="packages[]"][@value="package2"]/following-sibling::span[text()="package2"]'));
    }
}
