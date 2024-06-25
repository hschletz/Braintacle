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
        $routeHelper->method('getPathForRoute')->with('routeName', ['key' => 'value'])->willReturn('path');

        $pathForRouteFunction = new PathForRouteFunction($routeHelper);
        $this->assertEquals('path', $pathForRouteFunction('routeName', ['key' => 'value']));
    }
}
