<?php

namespace Braintacle\Authentication;

use Braintacle\Http\RouteHelper;
use Console\Validator\CsrfValidator;
use Laminas\Session\Container as Session;
use Model\Operator\AuthenticationService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ProcessLoginFormHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private Session $session,
        private CsrfValidator $csrfValidator,
        private AuthenticationService $authenticationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->authenticationService->hasIdentity()) {
            // A user is already logged in. Don't hijack an existing user session.
            throw new RuntimeException('A user is already logged in.');
        }

        $response = $this->response->withStatus(302);
        $formData = $request->getParsedBody();
        if (
            $this->csrfValidator->isValid($formData['_csrf']) &&
            $this->authenticationService->login($formData['user'], $formData['password'])
        ) {
            // Authentication successful.
            if (isset($this->session['originalUri'])) {
                // We got redirected here from another page. Redirect to original page.
                $redirectUri = $this->session['originalUri'];
                unset($this->session['originalUri']);

                return $response->withHeader('Location', (string) $redirectUri);
            } else {
                $redirectRoute = 'clientList';
            }
        } else {
            $this->session['invalidCredentials'] = true;
            $redirectRoute = 'loginPage';
        }

        $location = $this->routeHelper->getPathForRoute($redirectRoute);
        $response = $response->withHeader('Location', $location);

        return $response;
    }
}
