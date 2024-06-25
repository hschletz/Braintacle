<?php

namespace Braintacle\Client;

use Model\Client\ClientManager;
use Model\Config;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Download client as XML file.
 */
class ExportHandler implements RequestHandlerInterface
{
    use GetClientTrait;

    public function __construct(
        private ResponseInterface $response,
        private Config $config,
        private ClientManager $clientManager,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $client = $this->getClient($request);
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
