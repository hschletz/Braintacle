<?php

namespace Braintacle\Test\Group\Add;

use Braintacle\Group\Add\AddToGroupFormHandler;
use Braintacle\Group\Add\ExistingGroupFormData;
use Braintacle\Group\Add\NewGroupFormData;
use Braintacle\Group\Group;
use Braintacle\Group\Groups;
use Braintacle\Group\Membership;
use Braintacle\Http\RouteHelper;
use Braintacle\Search\SearchOperator;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Group\GroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddToGroupFormHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function runHandler(
        array $parsedBody,
        NewGroupFormData | ExistingGroupFormData $formData,
        MockObject | Group $group,
        MockObject | Groups $groups,
        GroupManager $groupManager
    ) {
        $formData->filter = '_filter';
        $formData->search = '_search';
        $formData->operator = SearchOperator::Equal;
        $formData->invert = true;
        $formData->membershipType = Membership::Never;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($parsedBody, get_class($formData))->willReturn($formData);

        $group->name = '_name';

        $groups->expects($this->once())->method('setSearchResults')->with(
            $group,
            $formData,
            Membership::Never,
        );

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper
            ->method('getPathForRoute')
            ->with('showGroupMembers', [], ['name' => '_name'])
            ->willReturn('redirect');

        $handler = new AddToGroupFormHandler($this->response, $dataProcessor, $groupManager, $groups, $routeHelper);
        $response = $handler->handle($this->request->withParsedBody($parsedBody));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['redirect']], $response);
    }

    public function testNewGroup()
    {
        $parsedBody = ['description' => ''];

        $formData = new NewGroupFormData();
        $formData->name = '_name';
        $formData->description = '_description';

        $group = $this->createMock(Group::class);

        $groups = $this->createMock(Groups::class);
        $groups->method('getGroup')->with('_name')->willReturn($group);

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->expects($this->once())->method('createGroup')->with('_name', '_description');

        $this->runHandler($parsedBody, $formData, $group, $groups, $groupManager);
    }

    public function testExistingGroup()
    {
        $parsedBody = [];

        $group = $this->createMock(Group::class);

        $formData = new ExistingGroupFormData();
        $formData->group = $group;

        $groups = $this->createMock(Groups::class);
        $groups->expects($this->never())->method('getGroup');

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->expects($this->never())->method('createGroup');


        $this->runHandler($parsedBody, $formData, $group, $groups, $groupManager);
    }
}
