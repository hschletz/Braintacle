<?php

namespace Braintacle\Template\Function;

use Braintacle\Http\RouteHelper;

/**
 * Retrieve path for named route.
 */
class PathForRouteFunction
{
    public function __construct(private RouteHelper $routeHelper)
    {
    }

    public function __invoke(string $name, array $routeArguments = [], array $queryParams = []): string
    {
        return $this->routeHelper->getPathForRoute($name, $routeArguments, $queryParams);
    }
}
