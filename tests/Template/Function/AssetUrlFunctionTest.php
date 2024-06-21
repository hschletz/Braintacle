<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Http\RouteHelper;
use Braintacle\Template\Function\AssetUrlFunction;
use PHPUnit\Framework\TestCase;

class AssetUrlFunctionTest extends TestCase
{
    public function testInvoke()
    {
        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getBasePath')->willReturn('/base');

        $assetUrlFunction = new AssetUrlFunction($routeHelper);
        $this->assertMatchesRegularExpression('#^/base/style.css\?\d+$#', $assetUrlFunction('style.css'));
    }
}
