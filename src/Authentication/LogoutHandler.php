<?php

namespace Braintacle\Authentication;

use Braintacle\Http\RouteHelper;
use Laminas\Authentication\AuthenticationServiceInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private AuthenticationServiceInterface $authenticationService,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticationService->clearIdentity();

        $location = $this->routeHelper->getPathForRoute('loginPage');
        $response = $this->response->withStatus(302)->withHeader('Location', $location);

        return $response;
    }
}
