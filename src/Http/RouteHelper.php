<?php

namespace Braintacle\Http;

use Slim\Interfaces\RouteParserInterface;

class RouteHelper
{
    private string $basePath;
    private RouteParserInterface $routeParser;

    /**
     * Detect application's base URI path.
     *
     * This should be used only for configuring the router. Handlers and
     * middleware should use one of the getters.
     *
     * @param array<string,mixed> $serverParams Content of $_SERVER
     */
    public static function detectBasePath(array $serverParams): string
    {
        $scriptName = $serverParams['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        if ($basePath == '/') {
            $basePath = '';
        }

        return $basePath;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setRouteParser(RouteParserInterface $routeParser): void
    {
        $this->routeParser = $routeParser;
    }

    /**
     * Get full url path of named route.
     */
    public function getPathForRoute(string $name, array $routeArguments = []): string
    {
        return $this->routeParser->urlFor($name, $routeArguments);
    }
}
