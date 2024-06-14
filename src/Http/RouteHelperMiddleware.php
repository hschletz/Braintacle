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
    public function __construct(private RouteHelper $routeHelper)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $this->routeHelper->setRouteContext($routeContext);

        return $handler->handle($request);
    }
}
