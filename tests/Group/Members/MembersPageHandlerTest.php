<?php

namespace Braintacle\Test\Group\Members;

use ArrayIterator;
use Braintacle\Direction;
use Braintacle\Group\Groups;
use Braintacle\Group\Members\MembersColumn;
use Braintacle\Group\Members\MembersPageHandler;
use Braintacle\Group\Members\MembersRequestParameters;
use Braintacle\Group\Membership;
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

#[CoversClass(MembersPageHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class MembersPageHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(): DOMXPath
    {
        $groupName = 'groupName';
        $queryParams = ['name' => $groupName];

        $group = new Group();
        $group->name = $groupName;
        $group->cacheCreationDate = new DateTime('2025-01-26 17:12:21');
        $group->cacheExpirationDate = new DateTime('2025-01-26 18:53:21');

        $requestParams = new MembersRequestParameters();
        $requestParams->group = $group;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams)->willReturn($requestParams);

        $client1 = new Client();
        $client1->id = 42;
        $client1->name = 'client_name';
        $client1->userName = 'user_name';
        $client1->inventoryDate = new DateTime('2014-04-09 18:56:12');
        $client1->membership = Membership::Manual;

        $client2 = new Client();
        $client2->id = 42;
        $client2->name = 'client_name';
        $client2->userName = 'user_name';
        $client2->inventoryDate = new DateTime('2014-04-09 18:56:12');
        $client2->membership = Membership::Automatic;

        $clients = new ArrayIterator([$client1, $client2]);

        $groups = $this->createMock(Groups::class);
        $groups
            ->method('getMembers')
            ->with($group, MembersColumn::InventoryDate, Direction::Descending)
            ->willReturn($clients);

        $templateEngine = $this->createTemplateEngine();

        $handler = new MembersPageHandler($this->response, $dataProcessor, $groups, $templateEngine);
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        return $this->getXPathFromMessage($response);
    }

    public function testHandler()
    {
        $xPath = $this->getXpath();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertXpathMatches(
            $xPath,
            '//ul[@class="navigation navigation_details"]/li[@class="active"]/a[@href="showGroupMembers/??name=groupName"]'
        );
        $this->assertXpathMatches($xPath, "//td[text()='_Last update:']/following::td[text()='Sonntag, 26.\xC2\xA0Januar 2025 um 17:12:21']");
        $this->assertXpathMatches($xPath, "//td[text()='_Next update:']/following::td[text()='Sonntag, 26.\xC2\xA0Januar 2025 um 18:53:21']");
        $this->assertXpathMatches($xPath, '//p[@class="textcenter"][text()="_Number of clients: 2"]');
        $this->assertXpathMatches($xPath, '(//table)[2]/tr[2]/td[1]/a[@href="showClientGroups/id=42?"][text()="client_name"]');
        $this->assertXpathMatches($xPath, '(//table)[2]/tr[2]/td[2][text()="user_name"]');
        $this->assertXpathMatches($xPath, '(//table)[2]/tr[2]/td[3][text()="09.04.14, 18:56"]');
        $this->assertXpathMatches($xPath, '(//table)[2]/tr[2]/td[4][text()="_manual"]');
        $this->assertXpathMatches($xPath, '(//table)[2]/tr[3]/td[4][text()="_automatic"]');
    }
}
