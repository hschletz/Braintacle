<?php

namespace Braintacle\Test\Authentication;

use Braintacle\Authentication\ProcessLoginFormHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Console\Validator\CsrfValidator;
use Laminas\Session\Container as Session;
use Model\Operator\AuthenticationService;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class ProcessLoginFormHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testWithIdentity()
    {
        $routeHelper = $this->createStub(RouteHelper::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->never())->method('offsetSet');
        $session->expects($this->never())->method('offsetUnset');

        $csrfValidator = $this->createStub(CsrfValidator::class);

        $authenticationService = $this->createMock(AuthenticationService::class);
        $authenticationService->method('hasIdentity')->willReturn(true);
        $authenticationService->expects($this->never())->method('login');

        $handler = new ProcessLoginFormHandler($this->response, $routeHelper, $session, $csrfValidator, $authenticationService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A user is already logged in.');
        $handler->handle($this->request);
    }

    public function testWithInvalidCsrfToken()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['_csrf' => 'token']);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('loginPage')->willReturn('/login');

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('offsetSet')->with('invalidCredentials', true);
        $session->expects($this->never())->method('offsetUnset');

        $csrfValidator = $this->createMock(CsrfValidator::class);
        $csrfValidator->method('isValid')->with('token')->willReturn(false);

        $authenticationService = $this->createMock(AuthenticationService::class);
        $authenticationService->method('hasIdentity')->willReturn(false);
        $authenticationService->expects($this->never())->method('login');

        $handler = new ProcessLoginFormHandler($this->response, $routeHelper, $session, $csrfValidator, $authenticationService);
        $response = $handler->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(['/login'], $response->getHeader('Location'));
    }

    public function testWithInvalidCredentials()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            '_csrf' => 'token',
            'user' => 'User',
            'password' => 'Password',
        ]);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('loginPage')->willReturn('/login');

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('offsetSet')->with('invalidCredentials', true);
        $session->expects($this->never())->method('offsetUnset');

        $csrfValidator = $this->createMock(CsrfValidator::class);
        $csrfValidator->method('isValid')->with('token')->willReturn(true);

        $authenticationService = $this->createMock(AuthenticationService::class);
        $authenticationService->method('hasIdentity')->willReturn(false);
        $authenticationService->expects($this->once())->method('login')->with('User', 'Password')->willReturn(false);

        $handler = new ProcessLoginFormHandler($this->response, $routeHelper, $session, $csrfValidator, $authenticationService);
        $response = $handler->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(['/login'], $response->getHeader('Location'));
    }

    public function testWithValidCredentialsAndDefaultRedirectTarget()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            '_csrf' => 'token',
            'user' => 'User',
            'password' => 'Password',
        ]);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('clientList')->willReturn('/clients');

        $session = $this->createMock(Session::class);
        $session->expects($this->never())->method('offsetGet');
        $session->expects($this->never())->method('offsetSet');
        $session->expects($this->never())->method('offsetUnset');
        $session->expects($this->once())->method('offsetExists')->with('originalUri')->willReturn(false);

        $csrfValidator = $this->createMock(CsrfValidator::class);
        $csrfValidator->method('isValid')->with('token')->willReturn(true);

        $authenticationService = $this->createMock(AuthenticationService::class);
        $authenticationService->method('hasIdentity')->willReturn(false);
        $authenticationService->expects($this->once())->method('login')->with('User', 'Password')->willReturn(true);

        $handler = new ProcessLoginFormHandler($this->response, $routeHelper, $session, $csrfValidator, $authenticationService);
        $response = $handler->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(['/clients'], $response->getHeader('Location'));
    }

    public function testWithValidCredentialsAndExplicitRedirectTarget()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            '_csrf' => 'token',
            'user' => 'User',
            'password' => 'Password',
        ]);

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('clientList')->willReturn('/clients');

        $session = $this->createMock(Session::class);
        $session->expects($this->never())->method('offsetSet');
        $session->expects($this->once())->method('offsetUnset')->with('originalUri');
        $session->method('offsetExists')->with('originalUri')->willReturn(true);
        $session->method('offsetGet')->with('originalUri')->willReturn(new Uri('/original'));

        $csrfValidator = $this->createMock(CsrfValidator::class);
        $csrfValidator->method('isValid')->with('token')->willReturn(true);

        $authenticationService = $this->createMock(AuthenticationService::class);
        $authenticationService->method('hasIdentity')->willReturn(false);
        $authenticationService->expects($this->once())->method('login')->with('User', 'Password')->willReturn(true);

        $handler = new ProcessLoginFormHandler($this->response, $routeHelper, $session, $csrfValidator, $authenticationService);
        $response = $handler->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(['/original'], $response->getHeader('Location'));
    }
}
