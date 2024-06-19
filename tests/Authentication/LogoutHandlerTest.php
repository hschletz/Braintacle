<?php

namespace Braintacle\Test\Authentication;

use Braintacle\Authentication\LogoutHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Laminas\Authentication\AuthenticationServiceInterface;
use PHPUnit\Framework\TestCase;

class LogoutHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandle()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('loginPage')->willReturn('/login');

        $authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $authenticationService->expects($this->once())->method('clearIdentity');

        $handler = new LogoutHandler($this->response, $routeHelper, $authenticationService);
        $response = $handler->handle($this->request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(['/login'], $response->getHeader('Location'));
    }
}
