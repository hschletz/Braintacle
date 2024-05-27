<?php

namespace Console\Test\Template;

use Braintacle\AppConfig;
use Console\Template\Filters\DateFormatFilter;
use Console\Template\TemplateRenderer;
use Console\Template\TemplateRendererFactory;
use Console\View\Helper\ConsoleScript;
use Console\View\Helper\ConsoleUrl;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\HelperPluginManager;
use Latte\Engine;
use Latte\Loaders\FileLoader;
use Library\Application;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Tests for TemplateRenderer except render()
 *
 * The render() method should be tested in TemplateRendererRenderTest.
 */
class TemplateRendererTest extends TestCase
{
    public function testFactory()
    {
        /** @var MockObject|TranslatorInterface */
        $translator = $this->createStub(Translator::class);
        $translator->method('translate')->willReturn('translated');

        /** @var Stub|ConsoleScript */
        $consoleScript = $this->createStub(ConsoleScript::class);

        /** @var MockObject|ConsoleUrl */
        $consoleUrl = $this->createMock(ConsoleUrl::class);
        $consoleUrl->method('__invoke')->willReturn('url');

        /** @var MockObject|HelperPluginManager */
        $viewHelperManager = $this->createMock(HelperPluginManager::class);
        $viewHelperManager->method('get')->willReturnMap([
            [ConsoleScript::class, null, $consoleScript],
            [ConsoleUrl::class, null, $consoleUrl],
        ]);

        /** @var MockObject|ContainerInterface */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            [DateFormatFilter::class, new DateFormatFilter()],
            [Translator::class, $translator],
            ['ViewHelperManager', $viewHelperManager],
        ]);

        $factory = new TemplateRendererFactory();
        /** @var TemplateRenderer */
        $templateRenderer = $factory($container, TemplateRenderer::class);
        $this->assertInstanceOf(TemplateRenderer::class, $templateRenderer);

        $engine = $templateRenderer->getEngine();
        $this->assertEquals('translated', $engine->invokeFunction('translate', ['message']));
        $this->assertEquals('url', $engine->invokeFunction('consoleUrl', []));
    }

    public function testService()
    {
        $application = Application::init('Console');
        $serviceManager = $application->getServiceManager();
        $serviceManager->setService('Library\UserConfig', []); // Dummy to make TemplateRenderer creation work
        $templateRenderer = $serviceManager->get(TemplateRenderer::class);
        $this->assertInstanceOf(TemplateRenderer::class, $templateRenderer);
    }

    public function testGetEngine()
    {
        $engine = new Engine();
        $templateRenderer = new TemplateRenderer($engine);
        $this->assertSame($engine, $templateRenderer->getEngine());
    }

    public function testLoader()
    {
        $engine = new Engine();
        $templateRenderer = new TemplateRenderer($engine);

        $loader = $templateRenderer->getEngine()->getLoader();
        $this->assertInstanceOf(FileLoader::class, $loader);
        $this->assertEquals(Application::getPath('templates'), rtrim($loader->getUniqueId(''), '/'));
    }
}
