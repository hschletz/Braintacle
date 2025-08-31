<?php

namespace Braintacle\Test\Client\Configuration;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Configuration\ClientConfigurationParameters;
use Braintacle\Client\Configuration\SetConfigurationHandler;
use Braintacle\Configuration\ClientConfig;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetConfigurationHandler::class)]
final class SetConfigurationHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandler()
    {
        $routeArguments = ['id' => '42'];
        $formData = ['key' => 'value'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = new Client();

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $configurationParameters = new ClientConfigurationParameters();

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willReturnMap([
            [$routeArguments, ClientRequestParameters::class, $clientRequestParameters],
            [$formData, ClientConfigurationParameters::class, $configurationParameters],
        ]);

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->expects($this->once())->method('setOptions')->with($client, $configurationParameters);

        $handler = new SetConfigurationHandler($this->response, $routeHelper, $dataProcessor, $clientConfig);

        $response = $handler->handle($this->request->withParsedBody($formData)->withUri($this->uri));
        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => [$this->uri]], $response);
    }
}
