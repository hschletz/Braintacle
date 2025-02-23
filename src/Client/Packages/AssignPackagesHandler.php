<?php

namespace Braintacle\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Package\Assignments;
use Braintacle\Package\AssignPackagesFormData;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Assign packages to a client.
 */
class AssignPackagesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private Assignments $assignments,
        private RouteHelper $routeHelper,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $formData = $request->getParsedBody();

        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;
        $packageNames = $this->dataProcessor->process($formData, AssignPackagesFormData::class)->packageNames;
        $this->assignments->assignPackages($packageNames, $client);

        return $this->response
            ->withStatus(302)
            ->withHeader(
                'Location',
                $this->routeHelper->getPathForRoute('showClientPackages', routeArguments: ['id' => $client->id])
            );
    }
}
