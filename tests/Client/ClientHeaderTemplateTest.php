<?php

namespace Braintacle\Test\Client;

use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\TemplateTestTrait;
use Exception;
use Model\Client\Client;
use Model\Client\WindowsInstallation;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class ClientHeaderTemplateTest extends TestCase
{
    use DomMatcherTrait;
    use TemplateTestTrait;

    private const Prefix = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
    private const Template = 'Pages/Client/Header.latte';

    public function testMenuEntriesForWindowsClients()
    {
        // phpcs:disable Generic.Files.LineLength.TooLong

        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = new WindowsInstallation();

        $engine = $this->createTemplateEngine();
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => '']);
        $xPath = $this->createXpath($content);

        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientGeneral/?id=42"][text()="_General"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientWindows/?id=42"][text()="_Windows"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientNetwork/?id=42"][text()="_Network"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientStorage/?id=42"][text()="_Storage"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientDisplay/?id=42"][text()="_Display"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientBios/?id=42"][text()="_BIOS"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientSystem/?id=42"][text()="_System"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientPrinters/?id=42"][text()="_Printers"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientSoftware/?id=42"][text()="_Software"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientMsOffice/?id=42"][text()="_MS Office"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientRegistry/?id=42"][text()="_Registry"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientVirtualMachines/?id=42"][text()="_Virtual machines"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientMisc/?id=42"][text()="_Misc"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientCustomFields/?id=42"][text()="_User defined"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientPackages/?id=42"][text()="_Packages"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientGroups/?id=42"][text()="_Groups"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientConfiguration/?id=42"][text()="_Configuration"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="export/?id=42"][text()="_Export"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="deleteClient/?id=42"][text()="_Delete"]');
    }

    public function testMenuEntriesForNonWindowsClients()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'name';
        $client->windows = null;

        $engine = $this->createTemplateEngine();
        $content = $engine->render(self::Template, ['client' => $client, 'currentAction' => '']);
        $xPath = $this->createXpath($content);

        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientGeneral/?id=42"][text()="_General"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientNetwork/?id=42"][text()="_Network"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientStorage/?id=42"][text()="_Storage"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientDisplay/?id=42"][text()="_Display"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientBios/?id=42"][text()="_BIOS"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientSystem/?id=42"][text()="_System"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientPrinters/?id=42"][text()="_Printers"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientSoftware/?id=42"][text()="_Software"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientVirtualMachines/?id=42"][text()="_Virtual machines"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientMisc/?id=42"][text()="_Misc"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientCustomFields/?id=42"][text()="_User defined"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientPackages/?id=42"][text()="_Packages"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientGroups/?id=42"][text()="_Groups"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="showClientConfiguration/?id=42"][text()="_Configuration"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="export/?id=42"][text()="_Export"]');
        $this->assertXpathMatches($xPath, self::Prefix . '/a[@href="deleteClient/?id=42"][text()="_Delete"]');
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
