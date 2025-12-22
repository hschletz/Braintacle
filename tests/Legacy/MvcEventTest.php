<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\MvcEvent;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MvcEvent::class)]
final class MvcEventTest extends TestCase
{
    public function testParam()
    {
        $mvcEvent = new MvcEvent();
        $this->assertNull($mvcEvent->getParam('foo'));
        $mvcEvent->setParam('foo', 'bar');
        $this->assertEquals('bar', $mvcEvent->getParam('foo'));
    }

    public function testRequest()
    {
        $request = new Request();
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);
        $this->assertSame($request, $mvcEvent->getRequest());
    }

    public function testResponse()
    {
        $response = new Response();
        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse($response);
        $this->assertSame($response, $mvcEvent->getResponse());
    }

    public function testResult()
    {
        $result = new Response();
        $mvcEvent = new MvcEvent();
        $mvcEvent->setResult($result);
        $this->assertSame($result, $mvcEvent->getResult());
    }

    public function testRouter()
    {
        $router = $this->createStub(RouteStackInterface::class);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRouter($router);
        $this->assertSame($router, $mvcEvent->getRouter());
    }

    public function testRouteMatch()
    {
        $routeMatch = new RouteMatch([]);
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRouteMatch($routeMatch);
        $this->assertSame($routeMatch, $mvcEvent->getRouteMatch());
    }
}
