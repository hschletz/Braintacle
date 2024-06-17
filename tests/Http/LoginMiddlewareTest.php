<?php

namespace Braintacle\Test\Http;

use Braintacle\Http\LoginMiddleware;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Container as Session;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoginMiddlewareTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testProcessAuthenticated()
    {
        $session = $this->createStub(Session::class);
        $routeHelper = $this->createStub(RouteHelper::class);

        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);
        $authenticationService->method('hasIdentity')->willReturn(true);

        $response = $this->createStub(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($this->identicalTo($this->request))->willReturn($response);

        $middleware = new LoginMiddleware($this->response, $session, $authenticationService, $routeHelper);
        $this->assertSame($response, $middleware->process($this->request, $handler));
    }

    public function testProcessNotAuthenticated()
    {
        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('offsetSet')->with('originalUri', $this->identicalTo($this->request->getUri()));

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('loginPage')->willReturn('/login');

        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);
        $authenticationService->method('hasIdentity')->willReturn(false);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $middleware = new LoginMiddleware($this->response, $session, $authenticationService, $routeHelper);
        $response = $middleware->process($this->request, $handler);

        $this->assertResponseStatusCode(302, $response);
        $this->assertEquals(['/login'], $response->getHeader('Location'));
    }
}
