<?php

namespace Braintacle\Test\Group;

use Braintacle\Group\GeneralPageHandler;
use Braintacle\Group\GroupRequestParameters;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\Function\TranslateFunction;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use Formotron\DataProcessor;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class GeneralPageHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    public function testHandler()
    {
        $groupName = '<groupName>';
        $queryParams = ['name' => $groupName];

        $group = $this->createMock(Group::class);
        $group->method('__get')->willReturnMap([
            ['name', $groupName],
            ['id', 42],
            ['description', 'groupDescription'],
            ['creationDate', new DateTime('2025-01-19T11:12:13')],
            ['dynamicMembersSql', 'groupSql'],
        ]);

        $requestData = new GroupRequestParameters();
        $requestData->group = $group;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams, GroupRequestParameters::class)->willReturn($requestData);

        $templateEngine = $this->createTemplateEngine();

        $handler = new GeneralPageHandler($this->response, $dataProcessor, $templateEngine);
        $response = $handler->handle($this->request->withQueryParams($queryParams));
        $xPath = $this->getXPathFromMessage($response);

        $this->assertXpathMatches($xPath, "//li[@class='active']/a[@href='showGroupGeneral/??name={$groupName}']");

        $this->assertXpathMatches($xPath, "//td[text()='_Name']/following::td[text()='{$groupName}']");
        $this->assertXpathMatches($xPath, "//td[text()='_ID']/following::td[text()='{$group->id}']");
        $this->assertXpathMatches($xPath, "//td[text()='_Description']/following::td[text()='{$group->description}']");
        $this->assertXpathMatches($xPath, "//td[text()='_Creation date']/following::td[text()='Sonntag, 19.\xC2\xA0Januar 2025 um 11:12:13']");
        $this->assertXpathMatches($xPath, "//td[text()='_SQL query']/following::td/code[text()='{$group->dynamicMembersSql}']");
    }
}
