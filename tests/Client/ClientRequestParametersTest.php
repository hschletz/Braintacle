<?php

namespace Braintacle\Test\Group;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\ClientTransformer;
use Braintacle\Test\DataProcessorTestTrait;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;

class ClientRequestParametersTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValid()
    {
        $client = $this->createStub(Client::class);

        $clientTransformer = $this->createMock(ClientTransformer::class);
        $clientTransformer->method('transform')->with('clientId')->willReturn($client);

        $dataProcessor = $this->createDataProcessor([ClientTransformer::class => $clientTransformer]);
        $clientRequestParameters = $dataProcessor->process(['id' => 'clientId'], ClientRequestParameters::class);

        $this->assertSame($client, $clientRequestParameters->client);
    }

    public function testGroupMissing()
    {
        $this->assertInvalidFormData(['client' => 'clientId'], ClientRequestParameters::class);
    }
}
