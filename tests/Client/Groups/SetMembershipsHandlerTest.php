<?php

namespace Braintacle\Test\Client\Groups;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Clients;
use Braintacle\Client\Groups\MembershipsFormData;
use Braintacle\Client\Groups\SetMembershipsHandler;
use Braintacle\Group\Membership;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;

class SetMembershipsHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandler()
    {
        $clientId = '42';
        $routeArguments = ['id' => $clientId];
        $parsedBody = ['groups' => ['foo' => '2']];
        $groups = ['foo' => Membership::Never];
        $redirectTo = 'redirect_to';

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);
        $routeHelper
            ->method('getPathForRoute')
            ->with('showClientGroups', ['id' => $clientId], [])
            ->willReturn($redirectTo);

        $client = $this->createStub(Client::class);
        $client->id = (int) $clientId;

        $requestParams = new ClientRequestParameters();
        $requestParams->client = $client;

        $formData = new MembershipsFormData();
        $formData->groups = $groups;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->willReturnMap([
            [$routeArguments, ClientRequestParameters::class, $requestParams],
            [$parsedBody, MembershipsFormData::class, $formData],
        ]);

        $clients = $this->createMock(Clients::class);
        $clients->expects($this->once())->method('setGroupMemberships')->with($client, $groups);

        $handler = new SetMembershipsHandler($this->response, $routeHelper, $dataProcessor, $clients);
        $response = $handler->handle($this->request->withParsedBody($parsedBody));

        $this->assertResponseStatusCode(302, $response);
        $this->assertEquals([$redirectTo], $response->getHeader('Location'));
    }
}
