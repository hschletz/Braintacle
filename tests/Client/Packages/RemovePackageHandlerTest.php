<?php

namespace Braintacle\Test\Client\Packages;

use Braintacle\Client\Packages\PackageActionParameters;
use Braintacle\Client\Packages\RemovePackageHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;

class RemovePackageHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $routeArguments = ['id' => '42'];
        $queryParams = ['package' => 'packageName', 'id' => 'ignored'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('removePackage')->with('packageName');

        $packageActionParameters = new PackageActionParameters();
        $packageActionParameters->client = $client;
        $packageActionParameters->packageName = 'packageName';

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with(['id' => '42', 'package' => 'packageName'], PackageActionParameters::class)
            ->willReturn($packageActionParameters);

        $handler = new RemovePackageHandler($this->response, $routeHelper, $dataProcessor);
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        $this->assertResponseStatusCode(200, $response);
    }
}
