<?php

namespace Braintacle\Client;

use Braintacle\Http\RouteHelper;
use Formotron\DataProcessor;
use Model\Config;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Download client as XML file.
 */
class ExportHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private Config $config,
        private DataProcessor $dataProcessor,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;

        $document = $client->toDomDocument();
        if ($this->config->validateXml) {
            $document->forceValid();
        }
        $filename = $document->getFilename();
        $xml = $document->saveXml();
        $response = $this->response
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"$filename\"")
            ->withHeader('Content-Length', (string) strlen($xml));
        $response->getBody()->write($xml);

        return $response;
    }
}
