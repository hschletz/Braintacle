<?php

namespace Braintacle\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Console\Form\Package\AssignPackagesForm;
use Formotron\DataProcessor;
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
        private AssignPackagesForm $assignPackagesForm,
        private RouteHelper $routeHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $formData = $request->getParsedBody();

        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;
        $this->assignPackagesForm->process($formData, $client);

        return $this->response
            ->withStatus(302)
            ->withHeader(
                'Location',
                $this->routeHelper->getPathForRoute('showClientPackages', routeArguments: ['id' => $client->id])
            );
    }
}
