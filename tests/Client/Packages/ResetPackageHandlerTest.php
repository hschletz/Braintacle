<?php

namespace Braintacle\Test\Client\Packages;

use Braintacle\Client\Packages\PackageActionParameters;
use Braintacle\Client\Packages\ResetPackageHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Package\Assignments;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;

class ResetPackageHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $routeArguments = ['id' => '42'];
        $queryParams = ['package' => 'packageName', 'id' => 'ignored'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = new Client();

        $packageActionParameters = new PackageActionParameters();
        $packageActionParameters->client = $client;
        $packageActionParameters->packageName = 'packageName';

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with(['id' => '42', 'package' => 'packageName'], PackageActionParameters::class)
            ->willReturn($packageActionParameters);

        $assignments = $this->createMock(Assignments::class);
        $assignments->expects($this->once())->method('resetPackage')->with('packageName', $client);

        $handler = new ResetPackageHandler($this->response, $routeHelper, $dataProcessor, $assignments);
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        $this->assertResponseStatusCode(200, $response);
    }
}
