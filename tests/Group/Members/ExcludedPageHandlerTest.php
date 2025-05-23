<?php

namespace Braintacle\Test\Group\Members;

use ArrayIterator;
use Braintacle\Direction;
use Braintacle\Group\Groups;
use Braintacle\Group\Members\ExcludedColumn;
use Braintacle\Group\Members\ExcludedPageHandler;
use Braintacle\Group\Members\ExcludedRequestParameters;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Group\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExcludedPageHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class ExcludedPageHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(array $clients): DOMXPath
    {
        $groupName = 'groupName';
        $queryParams = ['name' => $groupName];

        $group = new Group();
        $group->name = $groupName;
        $group->cacheCreationDate = new DateTime('2025-01-26 17:12:21');
        $group->cacheExpirationDate = new DateTime('2025-01-26 18:53:21');

        $requestParams = new ExcludedRequestParameters();
        $requestParams->group = $group;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams)->willReturn($requestParams);

        $groups = $this->createMock(Groups::class);
        $groups
            ->method('getExcludedClients')
            ->with($group, ExcludedColumn::InventoryDate, Direction::Descending)
            ->willReturn(new ArrayIterator($clients));

        $templateEngine = $this->createTemplateEngine();

        $handler = new ExcludedPageHandler($this->response, $dataProcessor, $groups, $templateEngine);
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        return $this->getXPathFromMessage($response);
    }

    public function testClients()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'client_name';
        $client->userName = 'user_name';
        $client->inventoryDate = new DateTime('2014-04-09 18:56:12');
        $client->membership = Client::MEMBERSHIP_ALWAYS;

        $xPath = $this->getXpath([$client]);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertXpathMatches(
            $xPath,
            '//ul[@class="navigation navigation_details"]/li[@class="active"]/a[@href="showGroupExcluded/??name=groupName"]'
        );
        $this->assertXpathMatches($xPath, '//p[@class="textcenter"][text()="_Number of clients: 1"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[1]/a[@href="showClientGroups/id=42?"][text()="client_name"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[2][text()="user_name"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[3][text()="09.04.14, 18:56"]');
    }

    public function testNoClients()
    {
        $xPath = $this->getXpath([]);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertXpathMatches(
            $xPath,
            '//ul[@class="navigation navigation_details"]/li[@class="active"]/a[@href="showGroupExcluded/??name=groupName"]'
        );
        $this->assertXpathMatches($xPath, '//p[@class="textcenter"][text()="_Number of clients: 0"]');
        $this->assertNotXpathMatches($xPath, '//table');
    }
}
