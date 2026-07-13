<?php

namespace Braintacle\Client\SubPage;

use Braintacle\Client\ClientDetails;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Information about a client's registry values (Windows only).
 */
class Registry implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private ClientDetails $clientDetails,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;

        $this->response->getBody()->write($this->templateEngine->render('Pages/Client/Registry.latte', [
            'client' => $client,
            'currentAction' => 'general',
            'data' => $this->clientDetails->getRegistryData($client),
        ]));

        return $this->response;
    }
}
