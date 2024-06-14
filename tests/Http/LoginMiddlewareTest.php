<?php

namespace Braintacle\Test\Http;

use Braintacle\Http\LoginMiddleware;
use Braintacle\Http\RouteHelper;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Container as Session;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoginMiddlewareTest extends TestCase
{
    public function testProcessAuthenticated()
    {
        $response = $this->createStub(ResponseInterface::class);
        $session = $this->createStub(Session::class);
        $routeHelper = $this->createStub(RouteHelper::class);

        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);
        $authenticationService->method('hasIdentity')->willReturn(true);

        $request = $this->createStub(ServerRequestInterface::class);
        $receivedResponse = $this->createStub(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($this->identicalTo($request))->willReturn($receivedResponse);

        $middleware = new LoginMiddleware($response, $session, $authenticationService, $routeHelper);
        $this->assertSame($receivedResponse, $middleware->process($request, $handler));
    }

    public function testProcessNotAuthenticated()
    {
        $response = new Response();
        $uri = $this->createStub(UriInterface::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('offsetSet')->with('originalUri', $this->identicalTo($uri));

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('loginPage')->willReturn('/login');

        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);
        $authenticationService->method('hasIdentity')->willReturn(false);

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $middleware = new LoginMiddleware($response, $session, $authenticationService, $routeHelper);
        $receivedResponse = $middleware->process($request, $handler);

        $this->assertEquals(302, $receivedResponse->getStatusCode());
        $this->assertEquals(['/login'], $receivedResponse->getHeader('Location'));
    }
}
