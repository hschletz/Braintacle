<?php

namespace Braintacle\Test\Client\Groups;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Groups\MembershipsFormData;
use Braintacle\Client\Groups\SetMembershipsHandler;
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
        $groups = ['foo' => Client::MEMBERSHIP_NEVER];
        $redirectTo = 'redirect_to';

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);
        $routeHelper->method('getPathForRoute')->with('showClientGroups', ['id' => $clientId], [])->willReturn($redirectTo);

        $client = $this->createMock(Client::class);
        $client->id = (int) $clientId;
        $client->expects($this->once())->method('setGroupMemberships')->with($groups);

        $requestParams = new ClientRequestParameters();
        $requestParams->client = $client;

        $formData = new MembershipsFormData();
        $formData->groups = $groups;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->willReturnMap([
            [$routeArguments, ClientRequestParameters::class, $requestParams],
            [$parsedBody, MembershipsFormData::class, $formData],
        ]);

        $handler = new SetMembershipsHandler($this->response, $routeHelper, $dataProcessor);
        $response = $handler->handle($this->request->withParsedBody($parsedBody));

        $this->assertResponseStatusCode(302, $response);
        $this->assertEquals([$redirectTo], $response->getHeader('Location'));
    }
}
