<?php

namespace Braintacle\Test\Http;

use Braintacle\Http\RouteHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Interfaces\RouteParserInterface;

class RouteHelperTest extends TestCase
{
    public static function detectBasePathProvider()
    {
        return [
            [null, ''],
            ['/index.php', ''],
            ['/path/index.php', '/path'],
            ['/path1/path2/index.php', '/path1/path2'],
        ];
    }

    #[DataProvider('detectBasePathProvider')]
    public function testDetectBasePath(?string $scriptName, string $basePath)
    {
        $serverEnvironment = ['SCRIPT_NAME' => $scriptName];

        /** @psalm-suppress InvalidArgument $scriptName may be NULL for this test only */
        $this->assertEquals($basePath, RouteHelper::detectBasePath($serverEnvironment));
    }

    public function testGetBasePath()
    {
        $routeHelper = new RouteHelper();
        $routeHelper->setBasePath('/base');
        $this->assertEquals('/base', $routeHelper->getBasePath());
    }

    public function testGetPathForRoute()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routeParser->method('urlFor')->with('routeName', ['arg' => 'foo'], ['key' => 'value'])->willReturn('/path');

        $routeHelper = new RouteHelper();
        $routeHelper->setRouteParser($routeParser);
        $this->assertEquals('/path', $routeHelper->getPathForRoute('routeName', ['arg' => 'foo'], ['key' => 'value']));
    }

    public function testGetPathForRouteDefaultArguments()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routeParser->method('urlFor')->with('routeName', [], [])->willReturn('/path');

        $routeHelper = new RouteHelper();
        $routeHelper->setRouteParser($routeParser);
        $this->assertEquals('/path', $routeHelper->getPathForRoute('routeName'));
    }
}
