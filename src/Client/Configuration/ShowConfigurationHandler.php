<?php

namespace Braintacle\Client\Configuration;

use Braintacle\Client\ClientDetails;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Configuration\ClientConfig;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Client configuration form.
 */
final class ShowConfigurationHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private ClientConfig $clientConfig,
        private ClientDetails $clientDetails,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $client = $this->dataProcessor->process(
            $this->routeHelper->getRouteArguments(),
            ClientRequestParameters::class,
        )->client;

        $values = $this->clientConfig->getOptions($client);
        $defaults = $this->clientConfig->getClientDefaults($client);
        $effective = $this->clientConfig->getEffectiveConfig($client);
        $networks = $this->clientDetails->getNetworks($client);

        $this->response->getBody()->write($this->templateEngine->render('Pages/Client/Configuration.latte', [
            'client' => $client,
            'currentAction' => 'configuration',
            'values' => $values,
            'defaults' => $defaults,
            'effectiveValues' => $effective,
            'networks' => $networks,
        ]));

        return $this->response;
    }
}
