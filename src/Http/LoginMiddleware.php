<?php

namespace Braintacle\Http;

use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Container as Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Redirect to login page if user is not authenticated.
 *
 * This middleware must not be attached to the login page/handler routes which
 * would result in a redirect loop.
 */
class LoginMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseInterface $response,
        private Session $session,
        private AuthenticationServiceInterface $authenticationService,
        private RouteHelper $routeHelper,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->authenticationService->hasIdentity()) {
            $response = $handler->handle($request);
        } else {
            // Preserve URI of current request for redirect after successful login
            $this->session['originalUri'] = $request->getUri();
            $loginPage = $this->routeHelper->getPathForRoute('loginPage');
            $response = $this->response->withStatus(302)->withHeader('Location', $loginPage);
        }

        return $response;
    }
}
