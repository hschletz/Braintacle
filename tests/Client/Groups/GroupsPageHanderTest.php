<?php

namespace Braintacle\Test\Client\Groups;

use ArrayIterator;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Groups\GroupsPageHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Group\Group;
use Model\Group\GroupManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupsPageHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class GroupsPageHanderTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(array $groups, Client $client): DOMXPath
    {
        $clientId = '42';
        $clientName = 'client_name';
        $routeArguments = ['id' => $clientId];

        $client->id = (int) $clientId;
        $client->name = $clientName;

        $requestParameters = new ClientRequestParameters();
        $requestParameters->client = $client;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($requestParameters);

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager
            ->expects($this->once())
            ->method('getGroups')
            ->with(null, null, 'Name')
            ->willReturn(new ArrayIterator($groups));

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $templateEngine = $this->createTemplateEngine();

        $handler = new GroupsPageHandler($this->response, $routeHelper, $dataProcessor, $groupManager, $templateEngine);
        $response = $handler->handle($this->request);
        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testNoGroups()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('getGroupMemberships');

        $xPath = $this->getXpath([], $client);

        $this->assertNotXpathMatches($xPath, '//h2');
        $this->assertNotXpathMatches($xPath, '//table');
        $this->assertNotXpathMatches($xPath, '//form[contains(@class, "form_groupmemberships")]');
    }

    public function testOnlyExcluded()
    {
        $group = new Group();
        $group->id = 1;
        $group->name = 'group';

        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('getGroupMemberships')->with(Client::MEMBERSHIP_ANY)->willReturn([
            1 => Client::MEMBERSHIP_NEVER,
        ]);

        $xPath = $this->getXpath([$group], $client);

        $this->assertXpathMatches($xPath, '//h2[text()="_Manage memberships"]');
        $this->assertXpathCount(1, $xPath, '//h2');
        $this->assertNotXpathMatches($xPath, '//table');
        $this->assertXpathMatches($xPath, '//form[contains(@class, "form_groupmemberships")]');
    }

    public function testMembers()
    {
        $group1 = new Group();
        $group1->id = 1;
        $group1->name = 'group1';

        $group2 = new Group();
        $group2->id = 2;
        $group2->name = 'group2';

        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('getGroupMemberships')->with(Client::MEMBERSHIP_ANY)->willReturn([
            1 => Client::MEMBERSHIP_AUTOMATIC,
            2 => Client::MEMBERSHIP_ALWAYS,
        ]);

        $xPath = $this->getXpath([$group1, $group2], $client);

        $this->assertXpathMatches($xPath, '//h2[text()="_Group memberships"]');

        $this->assertXpathMatches($xPath, '//tr[2]/td[1]/a[@href="showGroupGeneral/?name=group1"][text()="group1"]');
        $this->assertXpathMatches($xPath, '//tr[2]/td[2][text()="_automatic"]');

        $this->assertXpathMatches($xPath, '//tr[3]/td[1]/a[@href="showGroupGeneral/?name=group2"][text()="group2"]');
        $this->assertXpathMatches($xPath, '//tr[3]/td[2][text()="_manual"]');

        $this->assertXpathMatches($xPath, '//h2[text()="_Manage memberships"]');
        $this->assertXpathMatches($xPath, '//form[contains(@class, "form_groupmemberships")]');
    }

    public function testForm()
    {
        $group1 = new Group();
        $group1->id = 1;
        $group1->name = 'group1';

        $group2 = new Group();
        $group2->id = 2;
        $group2->name = 'group2';

        $group3 = new Group();
        $group3->id = 3;
        $group3->name = 'group3';

        $group4 = new Group();
        $group4->id = 4;
        $group4->name = 'group4';

        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('getGroupMemberships')->with(Client::MEMBERSHIP_ANY)->willReturn([
            1 => Client::MEMBERSHIP_AUTOMATIC,
            2 => Client::MEMBERSHIP_ALWAYS,
            3 => Client::MEMBERSHIP_NEVER,
            // group4 will be present in the form with implicit "automatic".
        ]);

        $xPath = $this->getXpath([$group1, $group2, $group3, $group4], $client);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertXpathMatches($xPath, '//form[@action="manageGroupMemberships/id=42?"]');

        $this->assertXpathMatches($xPath, '//fieldset/legend/a[@href="showGroupGeneral/?name=group1"][text()="group1"]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group1]"][@value="0"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group1]"][@value="1"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group1]"][@value="2"][not(@checked)]');

        $this->assertXpathMatches($xPath, '//fieldset/legend/a[@href="showGroupGeneral/?name=group2"][text()="group2"]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group2]"][@value="0"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group2]"][@value="1"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group2]"][@value="2"][not(@checked)]');

        $this->assertXpathMatches($xPath, '//fieldset/legend/a[@href="showGroupGeneral/?name=group3"][text()="group3"]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group3]"][@value="0"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group3]"][@value="1"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group3]"][@value="2"][@checked]');

        $this->assertXpathMatches($xPath, '//fieldset/legend/a[@href="showGroupGeneral/?name=group4"][text()="group4"]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group4]"][@value="0"][@checked]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group4]"][@value="1"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@type="radio"][@name="groups[group4]"][@value="2"][not(@checked)]');
    }
}
