<?php

namespace Braintacle\Test\Group\Packages;

use Braintacle\Group\Packages\RemovePackagesHandler;
use Braintacle\Group\Packages\RemovePackagesParameters;
use Braintacle\Package\Assignments;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class RemovePackagesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $packageName = 'packageName';
        $queryParams = ['name' => 'groupName', 'package' => $packageName];

        $group = $this->createMock(Group::class);

        $removePackagesParameters = new RemovePackagesParameters();
        $removePackagesParameters->group = $group;
        $removePackagesParameters->packageName = $packageName;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($queryParams, RemovePackagesParameters::class)
            ->willReturn($removePackagesParameters);

        $assignments = $this->createMock(Assignments::class);
        $assignments->expects($this->once())->method('unassignPackage')->with($packageName, $group);

        $handler = new RemovePackagesHandler($this->response, $dataProcessor, $assignments);
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        $this->assertResponseStatusCode(200, $response);
    }
}
