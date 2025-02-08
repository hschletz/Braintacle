<?php

namespace Braintacle\Test\Group\Add;

use Braintacle\Group\Add\AddToGroupFormHandler;
use Braintacle\Group\Add\ExistingGroupFormData;
use Braintacle\Group\Add\NewGroupFormData;
use Braintacle\Group\Membership;
use Braintacle\Http\RouteHelper;
use Braintacle\Search\SearchOperator;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Group\Group;
use Model\Group\GroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddToGroupFormHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;
    use MockeryPHPUnitIntegration;

    public function runHandler(
        array $parsedBody,
        NewGroupFormData | ExistingGroupFormData $formData,
        MockObject | Group $group,
        GroupManager $groupManager
    ) {
        $formData->filter = '_filter';
        $formData->search = '_search';
        $formData->operator = SearchOperator::Equal;
        $formData->invert = true;
        $formData->membershipType = Membership::Never;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($parsedBody, get_class($formData))->willReturn($formData);

        $group->method('__get')->with('name')->willReturn('_name');
        $group->expects($this->once())->method('setMembersFromQuery')->with(
            Membership::Never->value,
            '_filter',
            '_search',
            SearchOperator::Equal->value,
            true,
        );

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('showGroupMembers', [], ['name' => '_name'])->willReturn('redirect');

        $handler = new AddToGroupFormHandler($this->response, $dataProcessor, $groupManager, $routeHelper);
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

        $groupManager = Mockery::mock(GroupManager::class);
        $groupManager->shouldReceive('createGroup')->once()->ordered()->with('_name', '_description');
        $groupManager->shouldReceive('getGroup')->ordered()->with('_name')->andReturn($group);

        $this->runHandler($parsedBody, $formData, $group, $groupManager);
    }

    public function testExistingGroup()
    {
        $parsedBody = [];

        $group = $this->createMock(Group::class);

        $formData = new ExistingGroupFormData();
        $formData->group = $group;

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->expects($this->never())->method('createGroup');
        $groupManager->expects($this->never())->method('getGroup');

        $this->runHandler($parsedBody, $formData, $group, $groupManager);
    }
}
