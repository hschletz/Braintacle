<?php

namespace Braintacle\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Package\Assignments;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
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
        private Assignments $assignments,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
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
                    'assignedPackages' => $this->assignments->get($client),
                    'assignablePackages' => $this->assignments->getAssignablePackages($client),
                ]
            )
        );

        return $this->response;
    }
}
