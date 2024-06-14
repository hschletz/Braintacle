<?php

namespace Braintacle\Test\Http;

use Braintacle\Http\RouteHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;

class RouteHelperTest extends TestCase
{
    public static function getBasePathProvider()
    {
        return [
            [null, ''],
            ['/index.php', ''],
            ['/path/index.php', '/path'],
            ['/path1/path2/index.php', '/path1/path2'],
        ];
    }

    #[DataProvider('getBasePathProvider')]
    public function testGetBasePath(?string $scriptName, string $basePath)
    {
        $serverEnvironment = ['SCRIPT_NAME' => $scriptName];

        /** @psalm-suppress InvalidArgument $scriptName may be NULL for this test only */
        $this->assertEquals($basePath, RouteHelper::getBasePath($serverEnvironment));
    }

    public function testGetPathForRoute()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routeParser->method('urlFor')->with('routeName')->willReturn('/path');

        $routeContext = $this->createStub(RouteContext::class);
        $routeContext->method('getRouteParser')->willReturn($routeParser);

        $routeHelper = new RouteHelper();
        $routeHelper->setRouteContext($routeContext);
        $this->assertEquals('/path', $routeHelper->getPathForRoute('routeName'));
    }
}
