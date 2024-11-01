<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Http\RouteHelper;
use Braintacle\Template\Function\PathForRouteFunction;
use PHPUnit\Framework\TestCase;

class PathForRouteFunctionTest extends TestCase
{
    public function testInvoke()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('routeName', ['arg' => 'foo'], ['key' => 'value'])->willReturn('path');

        $pathForRouteFunction = new PathForRouteFunction($routeHelper);
        $this->assertEquals('path', $pathForRouteFunction('routeName', ['arg' => 'foo'], ['key' => 'value']));
    }

    public function testInvokeDefaultArguments()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('routeName', [], [])->willReturn('path');

        $pathForRouteFunction = new PathForRouteFunction($routeHelper);
        $this->assertEquals('path', $pathForRouteFunction('routeName'));
    }
}
