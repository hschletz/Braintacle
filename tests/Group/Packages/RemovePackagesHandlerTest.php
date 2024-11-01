<?php

namespace Braintacle\Test\Group\Packages;

use Braintacle\Group\Packages\RemovePackagesHandler;
use Braintacle\Group\Packages\RemovePackagesParameters;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\FormProcessor;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class RemovePackagesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $queryParams = ['name' => 'groupName', 'package' => 'packageName'];

        $group = $this->createMock(Group::class);
        $group->expects($this->once())->method('removePackage')->with('packageName');

        $removePackagesParameters = new RemovePackagesParameters();
        $removePackagesParameters->group = $group;
        $removePackagesParameters->packageName = 'packageName';

        $formProcessor = $this->createMock(FormProcessor::class);
        $formProcessor
            ->method('process')
            ->with($queryParams, RemovePackagesParameters::class)
            ->willReturn($removePackagesParameters);

        $handler = new RemovePackagesHandler($this->response, $formProcessor);
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        $this->assertResponseStatusCode(200, $response);
    }
}
