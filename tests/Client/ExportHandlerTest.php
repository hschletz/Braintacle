<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\ExportHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Config;
use Protocol\Message\InventoryRequest;
use Psr\Http\Message\ResponseInterface;

class ExportHandlerTest extends \PHPUnit\Framework\TestCase
{
    use HttpHandlerTestTrait;

    private function getResponse(Config $config, InventoryRequest $document): ResponseInterface
    {
        $routeArguments = ['id' => '42'];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $client = $this->createStub(Client::class);
        $client->method('toDomDocument')->willReturn($document);

        $requestParameters = new ClientRequestParameters();
        $requestParameters->client = $client;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($requestParameters);

        $handler = new ExportHandler($this->response, $routeHelper, $config, $dataProcessor);
        $response = $handler->handle($this->request);

        return $response;
    }

    public function testResponse()
    {
        $xmlContent = 'xml_content';

        $config = $this->createStub(Config::class);

        $document = $this->createStub(InventoryRequest::class);
        $document->method('getFilename')->willReturn('filename.xml');
        $document->method('saveXml')->willReturn($xmlContent);

        $response = $this->getResponse($config, $document);

        $this->assertResponseStatusCode(200, $response);
        $this->assertEquals([
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Content-Disposition' => ['attachment; filename="filename.xml"'],
            'Content-Length' => [(string) strlen($xmlContent)],
        ], $response->getHeaders());
        $this->assertResponseContent($xmlContent, $response);
    }

    public function testValidationSkipped()
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('__get')->with('validateXml')->willReturn('0');

        $document = $this->createMock(InventoryRequest::class);
        $document->method('saveXml')->willReturn('xml');
        $document->expects($this->never())->method('forceValid');

        $this->getResponse($config, $document);
    }

    public function testValidation()
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('__get')->with('validateXml')->willReturn('1');

        $document = $this->createMock(InventoryRequest::class);
        $document->method('saveXml')->willReturn('xml');
        $document->expects($this->once())->method('forceValid');

        $this->getResponse($config, $document);
    }
}
