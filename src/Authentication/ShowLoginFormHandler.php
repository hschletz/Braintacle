<?php

namespace Braintacle\Authentication;

use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Container as Session;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowLoginFormHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private AuthenticationServiceInterface $authenticationService,
        private Session $session,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->authenticationService->hasIdentity()) {
            // Don't show the login form if the user is already logged in.
            return $this->response->withStatus(302)->withHeader(
                'Location',
                $this->routeHelper->getPathForRoute('clientList')
            );
        }

        if (isset($this->session['invalidCredentials'])) {
            unset($this->session['invalidCredentials']);
            $params = ['invalidCredentials' => true];
        } else {
            $params = ['invalidCredentials' => false];
        }

        $this->response->getBody()->write($this->templateEngine->render('Pages/Login.latte', $params));

        return $this->response;
    }
}
