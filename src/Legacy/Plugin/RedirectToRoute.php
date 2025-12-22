<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\Http\RouteHelper;
use Laminas\Http\PhpEnvironment\Response;

final class RedirectToRoute
{
    use ControllerPluginTrait;

    public function __construct(private RouteHelper $routeHelper) {}

    public function __invoke(string $routeName, array $queryParams = []): Response
    {
        $response = $this->controller->getResponse();
        $response->setStatusCode(302);
        $response->getHeaders()->addHeaderLine(
            'Location',
            $this->routeHelper->getPathForRoute($routeName, queryParams: $queryParams),
        );

        return $response;
    }
}
