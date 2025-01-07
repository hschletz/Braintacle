<?php

namespace Braintacle\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Manage packages for a client.
 */
class ShowPackagesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private TemplateEngine $templateEngine,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $params = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class);
        $client = $params->client;

        $this->response->getBody()->write(
            $this->templateEngine->render(
                'Pages/Client/Packages.latte',
                [
                    'currentAction' => 'packages',
                    'client' => $client,
                    'assignedPackages' => $client->getPackageAssignments(),
                    'assignablePackages' => $client->getAssignablePackages(),
                ]
            )
        );

        return $this->response;
    }
}
