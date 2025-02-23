<?php

namespace Braintacle\Test\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Packages\AssignPackagesHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Package\Assignments;
use Braintacle\Package\AssignPackagesFormData;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;

class AssignPackagesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $routeArguments = ['id' => '42'];
        $packages = ['package1', 'package2'];
        $parsedBody = ['packages' => $packages];

        $client = new Client();
        $client->id = 42;

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $formData = new AssignPackagesFormData();
        $formData->packageNames = $packages;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->willReturnMap([
            [$routeArguments, ClientRequestParameters::class, $clientRequestParameters],
            [$parsedBody, AssignPackagesFormData::class, $formData],
        ]);

        $assignments = $this->createMock(Assignments::class);
        $assignments->expects($this->once())->method('assignPackages')->with($packages, $client);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);
        $routeHelper
            ->method('getPathForRoute')
            ->with('showClientPackages', ['id' => 42], [])
            ->willReturn('/showClientPackages');

        $handler = new AssignPackagesHandler($this->response, $dataProcessor, $assignments, $routeHelper);
        $response = $handler->handle($this->request->withParsedBody($parsedBody));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['/showClientPackages']], $response);
    }
}
