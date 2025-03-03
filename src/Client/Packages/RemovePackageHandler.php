<?php

namespace Braintacle\Client\Packages;

use Braintacle\Http\RouteHelper;
use Braintacle\Package\Assignments;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Remove a package from a client.
 */
class RemovePackageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private Assignments $assignments,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $queryParams = $request->getQueryParams();
        $params = $this->dataProcessor->process($routeArguments + $queryParams, PackageActionParameters::class);

        $this->assignments->unassignPackage($params->packageName, $params->client);

        return $this->response;
    }
}
