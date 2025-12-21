<?php

namespace Braintacle\Test\Legacy\Plugin;

use Braintacle\Http\RouteHelper;
use Braintacle\Legacy\Controller;
use Braintacle\Legacy\Plugin\RedirectToRoute;
use Laminas\Http\PhpEnvironment\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RedirectToRoute::class)]
final class RedirectToRouteTest extends TestCase
{
    public function testInvoke()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('path', [], ['foo' => 'bar'])->willReturn('path?foo=bar');

        $controller = $this->createStub(Controller::class);
        $controller->method('getResponse')->willReturn(new Response());

        $redirectToRoute = new RedirectToRoute($routeHelper);
        $redirectToRoute->setController($controller);
        $response = $redirectToRoute('path', ['foo' => 'bar']);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('path?foo=bar', $response->getHeaders()->get('Location')->getFieldValue());
    }
}
