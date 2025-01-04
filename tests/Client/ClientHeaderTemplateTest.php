<?php

namespace Braintacle\Test\Client;

use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Test\TemplateTestTrait;
use Console\Template\Functions\TranslateFunction;
use Exception;
use Library\Test\DomMatcherTrait;
use Model\Client\Client;
use Model\Client\WindowsInstallation;
use PHPUnit\Framework\TestCase;

class ClientHeaderTemplateTest extends TestCase
{
    use DomMatcherTrait;
    use TemplateTestTrait;

    private const Prefix = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
    private const Template = 'Pages/Client/Header.latte';

    public function testMenuEntriesForWindowsClients()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = new WindowsInstallation();

        $pathForRouteFunction = $this->createStub(PathForRouteFunction::class);
        $pathForRouteFunction->method('__invoke')->willReturnCallback(fn ($route) => '/' . $route);

        $translateFunction = $this->createStub(TranslateFunction::class);
        $translateFunction->method('__invoke')->willReturnCallback(fn ($message) => strtoupper($message));

        $engine = $this->createTemplateEngine([
            PathForRouteFunction::class => $pathForRouteFunction,
            TranslateFunction::class => $translateFunction,
        ]);
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => '']);
        $xPath = $this->createXpath($content);

        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientGeneral"][contains(text(), "GENERAL")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientWindows"][contains(text(), "WINDOWS")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientNetwork"][contains(text(), "NETWORK")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientStorage"][contains(text(), "STORAGE")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientDisplay"][contains(text(), "DISPLAY")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientBios"][contains(text(), "BIOS")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientSystem"][contains(text(), "SYSTEM")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientPrinters"][contains(text(), "PRINTERS")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientSoftware"][contains(text(), "SOFTWARE")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientMsOffice"][contains(text(), "MS OFFICE")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientRegistry"][contains(text(), "REGISTRY")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientVirtualMachines"][contains(text(), "VIRTUAL MACHINES")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientMisc"][contains(text(), "MISC")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientCustomFields"][contains(text(), "USER DEFINED")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientPackages"][contains(text(), "PACKAGES")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientGroups"][contains(text(), "GROUPS")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientConfiguration"][contains(text(), "CONFIGURATION")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/export"][contains(text(), "EXPORT")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/deleteClient"][contains(text(), "DELETE")]');
    }

    public function testMenuEntriesForNonWindowsClients()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = null;

        $pathForRouteFunction = $this->createStub(PathForRouteFunction::class);
        $pathForRouteFunction->method('__invoke')->willReturnCallback(fn ($route) => '/' . $route);

        $translateFunction = $this->createStub(TranslateFunction::class);
        $translateFunction->method('__invoke')->willReturnCallback(fn ($message) => strtoupper($message));

        $engine = $this->createTemplateEngine([
            PathForRouteFunction::class => $pathForRouteFunction,
            TranslateFunction::class => $translateFunction,
        ]);
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => '']);
        $xPath = $this->createXpath($content);

        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientGeneral"][contains(text(), "GENERAL")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientNetwork"][contains(text(), "NETWORK")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientStorage"][contains(text(), "STORAGE")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientDisplay"][contains(text(), "DISPLAY")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientBios"][contains(text(), "BIOS")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientSystem"][contains(text(), "SYSTEM")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientPrinters"][contains(text(), "PRINTERS")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientSoftware"][contains(text(), "SOFTWARE")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientVirtualMachines"][contains(text(), "VIRTUAL MACHINES")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientMisc"][contains(text(), "MISC")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientCustomFields"][contains(text(), "USER DEFINED")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientPackages"][contains(text(), "PACKAGES")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientGroups"][contains(text(), "GROUPS")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/showClientConfiguration"][contains(text(), "CONFIGURATION")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/export"][contains(text(), "EXPORT")]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/deleteClient"][contains(text(), "DELETE")]');
    }

    public function testLegacyRoutes()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = null;

        $pathForRouteFunction = $this->createMock(PathForRouteFunction::class);
        $pathForRouteFunction->method('__invoke')
            ->with($this->anything(), [], ['id' => 42])
            ->willReturnCallback(function (string $route, array $routeArguments, array $queryParams) {
                if ($routeArguments) {
                    $this->fail('pathForRoute() should not have been called with route arguments');
                } else {
                    return '/route?id=' . $queryParams['id'];
                }
            });

        $engine = $this->createTemplateEngine([
            PathForRouteFunction::class => $pathForRouteFunction,
        ]);
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => '']);
        $xPath = $this->createXpath($content);

        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/route?id=42"]');
    }

    public function testRouteArguments()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = null;

        $pathForRouteFunction = $this->createMock(PathForRouteFunction::class);
        $pathForRouteFunction->method('__invoke')->willReturnCallback(function (string $route, array $routeArguments) {
            if ($routeArguments) {
                return '/client/42/route';
            } else {
                throw new Exception(); // First invocation is without route arguments
            }
        });

        $engine = $this->createTemplateEngine([
            PathForRouteFunction::class => $pathForRouteFunction,
        ]);
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => '']);
        $xPath = $this->createXpath($content);

        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="/client/42/route"]');
    }

    public function testActiveRoute()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = null;

        $engine = $this->createTemplateEngine([]);
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => 'misc']);
        $xPath = $this->createXpath($content);

        $this->assertXpathCount(1, $xPath, self::Prefix . '[@class="active"]');
    }

    public function testExportRoute()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = null;

        $engine = $this->createTemplateEngine([]);
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => '']);
        $xPath = $this->createXpath($content);

        $this->assertXpathCount(1, $xPath, self::Prefix . '//a[@download]');
    }
}
