<?php

namespace Braintacle\Client\SubPage;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Information about a client's BIOS/UEFI.
 */
class Bios implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private TemplateEngine $templateEngine,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;

        $this->response->getBody()->write($this->templateEngine->render('Pages/Client/Bios.latte', [
            'client' => $client,
            'currentAction' => 'bios',
        ]));

        return $this->response;
    }
}
