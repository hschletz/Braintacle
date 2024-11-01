<?php

namespace Braintacle\Test\Group\Packages;

use Braintacle\Group\GroupRequestParameters;
use Braintacle\Group\Packages\AssignPackagesHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Console\Form\Package\AssignPackagesForm;
use Formotron\FormProcessor;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class AssignPackagesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $queryParams = ['name' => 'groupName'];
        $formData = ['packages' => ['packageName']];

        $group = new Group();
        $group->name = 'groupName';

        $groupRequestParameters = new GroupRequestParameters();
        $groupRequestParameters->group = $group;

        $formProcessor = $this->createMock(FormProcessor::class);
        $formProcessor
            ->method('process')
            ->with($queryParams, GroupRequestParameters::class)
            ->willReturn($groupRequestParameters);

        $assignPackagesForm = $this->createMock(AssignPackagesForm::class);
        $assignPackagesForm->expects($this->once())->method('process')->with($formData, $group);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper
            ->method('getPathForRoute')
            ->with('showGroupPackages', [], ['name' => 'groupName'])
            ->willReturn('/showGroupPackages');

        $handler = new AssignPackagesHandler($this->response, $formProcessor, $assignPackagesForm, $routeHelper);
        $response = $handler->handle($this->request->withQueryParams($queryParams)->withParsedBody($formData));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['/showGroupPackages']], $response);
    }
}
