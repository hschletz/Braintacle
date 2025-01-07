<?php

namespace Braintacle\Test\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Packages\AssignPackagesHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Console\Form\Package\AssignPackagesForm;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;

class AssignPackagesHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $routeArguments = ['id' => '42'];
        $formData = ['packages' => ['packageName']];

        $client = new Client();
        $client->id = 42;

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($clientRequestParameters);

        $assignPackagesForm = $this->createMock(AssignPackagesForm::class);
        $assignPackagesForm->expects($this->once())->method('process')->with($formData, $client);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);
        $routeHelper
            ->method('getPathForRoute')
            ->with('showClientPackages', ['id' => 42], [])
            ->willReturn('/showClientPackages');

        $handler = new AssignPackagesHandler($this->response, $dataProcessor, $assignPackagesForm, $routeHelper);
        $response = $handler->handle($this->request->withParsedBody($formData));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['/showClientPackages']], $response);
    }
}
