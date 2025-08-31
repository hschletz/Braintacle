<?php

namespace Braintacle\Client\Configuration;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Configuration\ClientConfig;
use Braintacle\Http\RouteHelper;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Set client configuration.
 */
final class SetConfigurationHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private ClientConfig $clientConfig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $client = $this->dataProcessor->process(
            $this->routeHelper->getRouteArguments(),
            ClientRequestParameters::class
        )->client;

        $formData = $request->getParsedBody();
        $config = $this->dataProcessor->process($formData, ClientConfigurationParameters::class);
        $this->clientConfig->setOptions($client, $config);

        return $this->response->withStatus(302)->withHeader('Location', (string) $request->getUri());
    }
}
