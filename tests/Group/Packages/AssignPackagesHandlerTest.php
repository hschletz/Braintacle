<?php

namespace Braintacle\Test\Group\Packages;

use Braintacle\Group\Group;
use Braintacle\Group\GroupRequestParameters;
use Braintacle\Group\Packages\AssignPackagesHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Package\Assignments;
use Braintacle\Package\AssignPackagesFormData;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use PHPUnit\Framework\TestCase;

class AssignPackagesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $queryParams = ['name' => 'groupName'];
        $packages = ['package1', 'package2'];
        $parsedBody = ['packages' => $packages];

        $group = new Group();
        $group->name = 'groupName';

        $groupRequestParameters = new GroupRequestParameters();
        $groupRequestParameters->group = $group;

        $formData = new AssignPackagesFormData();
        $formData->packageNames = $packages;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->willReturnMap([
            [$queryParams, GroupRequestParameters::class, $groupRequestParameters],
            [$parsedBody, AssignPackagesFormData::class, $formData],
        ]);

        $assignments = $this->createMock(Assignments::class);
        $assignments->expects($this->once())->method('assignPackages')->with($packages, $group);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper
            ->method('getPathForRoute')
            ->with('showGroupPackages', [], ['name' => 'groupName'])
            ->willReturn('/showGroupPackages');

        $handler = new AssignPackagesHandler($this->response, $dataProcessor, $assignments, $routeHelper);
        $response = $handler->handle($this->request->withQueryParams($queryParams)->withParsedBody($parsedBody));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['/showGroupPackages']], $response);
    }
}
