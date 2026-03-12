<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Http\RouteHelper;
use Braintacle\Legacy\Plugin\FlashMessenger;
use Braintacle\Package\Build\BuildPage;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use Model\Config;
use Model\Package\Package;
use Model\Package\PackageManager;
use Model\Package\RuntimeException as PackageRuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BuildPage::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
final class BuildPageTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function createHandler(
        ?RouteHelper $routeHelper = null,
        ?PackageManager $packageManager = null,
        ?Config $config = null,
        ?FlashMessenger $flashMessenger = null,
        ?TemplateEngine $templateEngine = null,
    ): BuildPage {
        return new BuildPage(
            $this->response,
            $routeHelper ?? $this->createStub(RouteHelper::class),
            $packageManager ?? $this->createStub(PackageManager::class),
            $config ?? $this->createStub(Config::class),
            $flashMessenger ?? $this->createStub(FlashMessenger::class),
            $templateEngine ?? $this->createTemplateEngine(),
        );
    }

    public function testBuild()
    {
        $config = $this->createStub(Config::class);
        $config->method('__get')->willReturnMap([
            ['defaultPlatform', 'linux'],
            ['defaultAction', 'execute'],
            ['defaultActionParam', '_actionParam'],
            ['defaultPackagePriority', 5],
            ['defaultMaxFragmentSize', 42],
            ['defaultWarn', false],
            ['defaultWarnMessage', '_warnMessage'],
            ['defaultWarnCountdown', 60],
            ['defaultWarnAllowAbort', false],
            ['defaultWarnAllowDelay', false],
            ['defaultPostInstMessage', '_postInstMessage'],
        ]);

        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->expects($this->never())->method('addErrorMessage');

        $handler = $this->createHandler(config: $config, flashMessenger: $flashMessenger);
        $response = $handler->handle($this->request);

        $this->assertResponseStatusCode(200, $response);

        $xPath = $this->getXPathFromMessage($response);
        $this->assertXpathMatches($xPath, '//input[@name="name"][not(@value)]');
        $this->assertXpathMatches($xPath, '//textarea[@name="comment"][text()=""]');
        $this->assertXpathMatches($xPath, '//select[@name="platform"]/option[@value="linux"][@selected]');
        $this->assertXpathMatches($xPath, '//select[@name="action"]/option[@value="execute"][@selected]');
        $this->assertXpathMatches($xPath, '//input[@name="actionParam"][@value="_actionParam"]');
        $this->assertXpathMatches($xPath, '//input[@name="priority"][@value="5"]');
        $this->assertXpathMatches($xPath, '//input[@name="maxFragmentSize"][@value="42"]');
        $this->assertXpathMatches($xPath, '//input[@name="warn"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@name="warnCountdown"][@value="60"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="warnMessage"][text()="_warnMessage"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="postInstMessage"][text()="_postInstMessage"]');
        $this->assertXpathMatches($xPath, '//input[@name="warnAllowAbort"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@name="warnAllowDelay"][not(@checked)]');
        $this->assertXpathCount(0, $xPath, '//input[starts-with(@name, "deploy")]');
        $this->assertXpathCount(0, $xPath, '//*[@class="error"]');
    }

    public function testUpdate()
    {
        $package = $this->createStub(Package::class);
        $package->method('__get')->willReturnMap([
            ['name', '_name'],
            ['comment', '_comment'],
            ['platform', 'linux'],
            ['deployAction', 'execute'],
            ['actionParam', '_actionParam'],
            ['priority', 5],
            ['warn', false],
            ['warnMessage', '_warnMessage'],
            ['warnCountdown', 60],
            ['warnAllowAbort', false],
            ['warnAllowDelay', false],
            ['postInstMessage', '_postInstMessage'],
        ]);

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getPackage')->with('oldPackage')->willReturn($package);

        $config = $this->createStub(Config::class);
        $config->method('__get')->willReturnMap([
            ['defaultMaxFragmentSize', 42],
            ['defaultDeployPending', false],
            ['defaultDeployRunning', false],
            ['defaultDeploySuccess', false],
            ['defaultDeployError', false],
            ['defaultDeployGroup', false],
        ]);

        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->expects($this->never())->method('addErrorMessage');

        $handler = $this->createHandler(
            packageManager: $packageManager,
            config: $config,
            flashMessenger: $flashMessenger,
        );
        $response = $handler->handle($this->request->withQueryParams(['updateFrom' => 'oldPackage']));

        $this->assertResponseStatusCode(200, $response);

        $xPath = $this->getXPathFromMessage($response);
        $this->assertXpathMatches($xPath, '//input[@name="name"][@value="_name"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="comment"][text()="_comment"]');
        $this->assertXpathMatches($xPath, '//select[@name="platform"]/option[@value="linux"][@selected]');
        $this->assertXpathMatches($xPath, '//select[@name="action"]/option[@value="execute"][@selected]');
        $this->assertXpathMatches($xPath, '//input[@name="actionParam"][@value="_actionParam"]');
        $this->assertXpathMatches($xPath, '//input[@name="priority"][@value="5"]');
        $this->assertXpathMatches($xPath, '//input[@name="maxFragmentSize"][@value="42"]');
        $this->assertXpathMatches($xPath, '//input[@name="warn"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@name="warnCountdown"][@value="60"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="warnMessage"][text()="_warnMessage"]');
        $this->assertXpathMatches($xPath, '//textarea[@name="postInstMessage"][text()="_postInstMessage"]');
        $this->assertXpathMatches($xPath, '//input[@name="warnAllowAbort"][not(@checked)]');
        $this->assertXpathMatches($xPath, '//input[@name="warnAllowDelay"][not(@checked)]');
        $this->assertXpathCount(5, $xPath, '//input[starts-with(@name, "deploy")]');
        $this->assertXpathCount(5, $xPath, '//input[starts-with(@name, "deploy")][not(@checked)]');
        $this->assertXpathCount(0, $xPath, '//*[@class="error"]');
    }

    #[TestWith(['warn', 'warn'])]
    #[TestWith(['warnAllowAbort', 'warnAllowAbort'])]
    #[TestWith(['warnAllowDelay', 'warnAllowDelay'])]
    #[TestWith(['deployPending', 'defaultDeployPending'])]
    #[TestWith(['deployRunning', 'defaultDeployRunning'])]
    #[TestWith(['deploySuccess', 'defaultDeploySuccess'])]
    #[TestWith(['deployError', 'defaultDeployError'])]
    #[TestWith(['deployGroups', 'defaultDeployGroups'])]
    public function testCheckboxChecked(string $elementName, string $property)
    {
        $package = $this->createStub(Package::class);
        $package->method('__get')->willReturnCallback(fn($key) => match ($key) {
            'warn', 'warnAllowAbort', 'warnAllowDelay' => $key == $property,
            default => ''
        });

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getPackage')->with('oldPackage')->willReturn($package);

        $config = $this->createStub(Config::class);
        $config->method('__get')->willReturnCallback(
            fn($key) => str_starts_with($key, 'defaultDeploy') ? ($key == $property) : ''
        );

        $handler = $this->createHandler(packageManager: $packageManager, config: $config);
        $response = $handler->handle($this->request->withQueryParams(['updateFrom' => 'oldPackage']));
        $xPath = $this->getXPathFromMessage($response);

        $this->assertXpathCount(1, $xPath, '//input[@checked]');
        $this->assertXpathMatches($xPath, "//input[@name='$elementName'][@checked]");
    }

    public function testPackageUnreadable()
    {
        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->with('packagesList')->willReturn('packages_list');

        $packageManager = $this->createStub(PackageManager::class);
        $packageManager->method('getPackage')->willThrowException(new PackageRuntimeException('exception message'));

        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->expects($this->once())->method('addErrorMessage')->with('exception message');

        $handler = $this->createHandler(
            routeHelper: $routeHelper,
            packageManager: $packageManager,
            flashMessenger: $flashMessenger,
        );
        $response = $handler->handle($this->request->withQueryParams(['updateFrom' => 'oldPackage']));

        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => ['packages_list']], $response);
    }
}
