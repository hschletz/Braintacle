<?php

namespace Braintacle\Http;

use Slim\Routing\RouteContext;

class RouteHelper
{
    private RouteContext $routeContext;

    /**
     * Detect application's base URI path.
     *
     * This should be used only for configuring the router. Handlers and
     * middleware should use one of the getters.
     *
     * @param array<string,mixed> $serverParams Content of $_SERVER
     */
    public static function getBasePath(array $serverParams): string
    {
        $scriptName = $serverParams['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        if ($basePath == '/') {
            $basePath = '';
        }

        return $basePath;
    }

    public function setRouteContext(RouteContext $routeContext): void
    {
        $this->routeContext = $routeContext;
    }

    /**
     * Get full url path of named route.
     */
    public function getPathForRoute(string $name): string
    {
        return $this->routeContext->getRouteParser()->urlFor($name);
    }
}
