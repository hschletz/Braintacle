<?php

namespace Braintacle\Test\Group\Overview;

use ArrayIterator;
use Braintacle\Direction;
use Braintacle\FlashMessages;
use Braintacle\Group\Overview\OverviewColumn;
use Braintacle\Group\Overview\OverviewHandler;
use Braintacle\Group\Overview\OverviewRequestParameters;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Group\Group;
use Model\Group\GroupManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OverviewHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class OverviewHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(array $groups, array $messages): DOMXPath
    {
        $queryParams = ['order' => 'name', 'direction', 'asc'];
        $requestParameters = new OverviewRequestParameters();

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($queryParams, OverviewRequestParameters::class)
            ->willReturn($requestParameters);

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->method('getGroups')->with(null, null, 'Name', 'asc')->willReturn(new ArrayIterator($groups));

        $templateEngine = $this->createTemplateEngine();

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with(FlashMessages::Success)->willReturn($messages);

        $handler = new OverviewHandler($this->response, $dataProcessor, $groupManager, $templateEngine, $flashMessages);
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        return $this->getXPathFromMessage($response);
    }

    public function testNoGroups()
    {
        $xPath = $this->getXpath([], []);
        $this->assertNotXpathMatches($xPath, '//table');
        $this->assertXpathMatches($xPath, '//p[@class="textcenter"][text()="_No groups defined."]');
    }

    public function testGroups()
    {
        $group1 = new Group();
        $group1->name = 'name1';
        $group1->creationDate = new DateTime('2014-04-06T11:55:33');
        $group1->description = 'description1';

        $group2 = new Group();
        $group2->name = 'name2';
        $group2->creationDate = new DateTime('2025-01-20T16:44:33');
        $group2->description = 'description2';

        $xPath = $this->getXpath([$group1, $group2], []);

        $this->assertXpathMatches($xPath, '//tr[2]/td[1]/a[@href="showGroupGeneral/?name=name1"][text()="name1"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[2][text()="06.04.14, 11:55"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[3][text()="description1"]');

        $this->assertXpathMatches($xPath, '//tr[3]/td[1]/a[@href="showGroupGeneral/?name=name2"][text()="name2"]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[2][text()="20.01.25, 16:44"]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[3][text()="description2"]');

        $this->assertNotXpathMatches($xPath, '//p[@class="textcenter"][text()="_No groups defined."]');
    }

    public function testNoFlashMessage()
    {
        $xPath = $this->getXpath([], []);
        $this->assertNotXpathMatches($xPath, '//p[@class="success"]');
    }

    public function testFlashMessage()
    {
        $xPath = $this->getXpath([], ['message']);
        $this->assertXpathMatches($xPath, '//p[@class="success"][text()="message"]');
    }

    public function testOrderParams()
    {
        $requestParameters = new OverviewRequestParameters();
        $requestParameters->order = OverviewColumn::Description;
        $requestParameters->direction = Direction::Descending;

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willReturn($requestParameters);

        $groupManager = $this->createStub(GroupManager::class);

        $templateEngine = $this->createMock(TemplateEngine::class);
        $templateEngine
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function ($context) {
                    $this->assertEquals('Description', $context['order']);
                    $this->assertEquals(Direction::Descending, $context['direction']);

                    return true;
                })
            );

        $flashMessages = $this->createStub(FlashMessages::class);

        $handler = new OverviewHandler($this->response, $dataProcessor, $groupManager, $templateEngine, $flashMessages);
        $handler->handle($this->request);
    }
}
