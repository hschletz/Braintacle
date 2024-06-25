<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\ExportHandler;
use Braintacle\Test\HttpHandlerTestTrait;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use Model\Client\Client;
use Model\Client\ClientManager;
use Model\Config;
use Protocol\Message\InventoryRequest;

class ExportHandlerTest extends \PHPUnit\Framework\TestCase
{
    use HttpHandlerTestTrait;
    use MockeryPHPUnitIntegration;

    public function testResponse()
    {
        $xmlContent = 'xml_content';

        $config = $this->createStub(Config::class);

        $document = $this->createStub(InventoryRequest::class);
        $document->method('getFilename')->willReturn('filename.xml');
        $document->method('saveXml')->willReturn($xmlContent);

        $client = $this->createStub(Client::class);
        $client->method('toDomDocument')->willReturn($document);

        $clientManager = $this->createStub(ClientManager::class);

        /** @var Mock|ExportHandler */
        $handler = Mockery::mock(ExportHandler::class, [$this->response, $config, $clientManager])->makePartial();
        $handler->shouldReceive('getClient')->once()->with($this->request)->andReturn($client);

        $response = $handler->handle($this->request);

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

        $client = $this->createStub(Client::class);
        $client->method('toDomDocument')->willReturn($document);

        $clientManager = $this->createStub(ClientManager::class);

        /** @var Mock|ExportHandler */
        $handler = Mockery::mock(ExportHandler::class, [$this->response, $config, $clientManager])->makePartial();
        $handler->shouldReceive('getClient')->once()->with($this->request)->andReturn($client);
        $handler->handle($this->request);
    }

    public function testValidation()
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('__get')->with('validateXml')->willReturn('1');

        $document = $this->createMock(InventoryRequest::class);
        $document->method('saveXml')->willReturn('xml');
        $document->expects($this->once())->method('forceValid');

        $client = $this->createStub(Client::class);
        $client->method('toDomDocument')->willReturn($document);

        $clientManager = $this->createStub(ClientManager::class);

        /** @var Mock|ExportHandler */
        $handler = Mockery::mock(ExportHandler::class, [$this->response, $config, $clientManager])->makePartial();
        $handler->shouldReceive('getClient')->once()->with($this->request)->andReturn($client);
        $handler->handle($this->request);
    }
}
