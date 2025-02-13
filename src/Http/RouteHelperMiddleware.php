<?php

namespace Braintacle\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Prepare the RouteHelper instance.
 *
 * The RouteContext is available only after routing has started. This middleware
 * must therefore be added before Slim's routing middleware.
 *
 * @codeCoverageIgnore
 */
class RouteHelperMiddleware implements MiddlewareInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private RouteHelper $routeHelper) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // The route context will not contain the base path because Slim will
        // inject it only after the middleware stack has been processed.
        $routeContext = RouteContext::fromRequest($request);
        $this->routeHelper->setRouteParser($routeContext->getRouteParser());
        $this->routeHelper->setBasePath(RouteHelper::detectBasePath($request->getServerParams()));
        $this->routeHelper->setRouteArguments($routeContext->getRoutingResults()->getRouteArguments());

        return $handler->handle($request);
    }
}
