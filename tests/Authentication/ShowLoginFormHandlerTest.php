<?php

namespace Braintacle\Test\Authentication;

use Braintacle\Authentication\ShowLoginFormHandler;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Container as Session;
use PHPUnit\Framework\TestCase;

class ShowLoginFormHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testRedirectIfAlreadyLoggedIn()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('clientList')->willReturn('/clients');

        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);
        $authenticationService->method('hasIdentity')->willReturn(true);

        $session = $this->createMock(Session::class);
        $session->expects($this->never())->method('offsetExists');
        $session->expects($this->never())->method('offsetUnset');

        $templateEngine = $this->createTemplateEngine();

        $handler = new ShowLoginFormHandler($this->response, $routeHelper, $authenticationService, $session, $templateEngine);
        $response = $handler->handle($this->request);
        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['/clients']], $response);
    }

    public function testWithoutMessage()
    {
        $routeHelper = $this->createStub(RouteHelper::class);

        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);
        $authenticationService->method('hasIdentity')->willReturn(false);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('offsetExists')->with('invalidCredentials')->willReturn(false);
        $session->expects($this->never())->method('offsetUnset');

        $templateEngine = $this->createTemplateEngine();

        $handler = new ShowLoginFormHandler($this->response, $routeHelper, $authenticationService, $session, $templateEngine);
        $response = $handler->handle($this->request);
        $this->assertResponseStatusCode(200, $response);

        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(0, $xPath->query('//p[@class="error"]'));
        $this->assertCount(1, $xPath->query('//form[@method="post"][@action="__pathForRoute_loginHandler"]'));
        $this->assertCount(1, $xPath->query('//form//input[@type="hidden"][@name="_csrf"][@value="__csrfToken"]'));
        $this->assertCount(1, $xPath->query('//form//input[@type="text"][@name="user"]'));
        $this->assertCount(1, $xPath->query('//form//input[@type="password"][@name="password"]'));
    }

    public function testWithMessage()
    {
        $routeHelper = $this->createStub(RouteHelper::class);

        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);
        $authenticationService->method('hasIdentity')->willReturn(false);

        $session = $this->createMock(Session::class);
        $session->method('offsetExists')->with('invalidCredentials')->willReturn(true);
        $session->expects($this->once())->method('offsetUnset')->with('invalidCredentials');

        $templateEngine = $this->createTemplateEngine();

        $handler = new ShowLoginFormHandler($this->response, $routeHelper, $authenticationService, $session, $templateEngine);
        $response = $handler->handle($this->request);
        $this->assertResponseStatusCode(200, $response);

        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(1, $xPath->query('//p[@class="error"]'));
        $this->assertCount(1, $xPath->query('//form[@method="post"][@action="__pathForRoute_loginHandler"]'));
        $this->assertCount(1, $xPath->query('//form//input[@type="hidden"][@name="_csrf"][@value="__csrfToken"]'));
        $this->assertCount(1, $xPath->query('//form//input[@type="text"][@name="user"]'));
        $this->assertCount(1, $xPath->query('//form//input[@type="password"][@name="password"]'));
    }
}
