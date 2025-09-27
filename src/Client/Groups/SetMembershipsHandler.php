<?php

namespace Braintacle\Client\Groups;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Clients;
use Braintacle\Http\RouteHelper;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Set group memberships.
 */
class SetMembershipsHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private Clients $clients,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $groups = $this->dataProcessor->process($request->getParsedBody(), MembershipsFormData::class)->groups;
        $client = $this->dataProcessor->process(
            $this->routeHelper->getRouteArguments(),
            ClientRequestParameters::class
        )->client;
        $this->clients->setGroupMemberships($client, $groups);

        return $this->response->withStatus(302)->withHeader(
            'Location',
            $this->routeHelper->getPathForRoute('showClientGroups', ['id' => $client->id])
        );
    }
}
