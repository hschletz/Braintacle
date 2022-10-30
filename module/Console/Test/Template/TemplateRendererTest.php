<?php

namespace Console\Test\Template;

use Console\Template\TemplateRenderer;
use Console\Template\TemplateRendererFactory;
use Console\View\Helper\ConsoleUrl;
use ErrorException;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\HelperPluginManager;
use Laminas\View\Model\ViewModel;
use Latte\Engine;
use Latte\Loaders\FileLoader;
use Library\Application;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Throwable;

class TemplateRendererTest extends TestCase
{
    public function testFactory()
    {
        /** @var MockObject|TranslatorInterface */
        $translator = $this->createStub(Translator::class);
        $translator->method('translate')->willReturn('translated');

        /** @var MockObject|ConsoleUrl */
        $consoleUrl = $this->createMock(ConsoleUrl::class);
        $consoleUrl->method('__invoke')->willReturn('url');

        /** @var MockObject|HelperPluginManager */
        $viewHelperManager = $this->createMock(HelperPluginManager::class);
        $viewHelperManager->method('get')->with(ConsoleUrl::class)->willReturn($consoleUrl);

        /** @var MockObject|ContainerInterface */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
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

    public function testRenderWithName()
    {
        $engine = $this->createMock(Engine::class);
        $engine->method('renderToString')->with('template', ['values'])->willReturn('content');

        $templateRenderer = new TemplateRenderer($engine);
        $this->assertEquals('content', $templateRenderer->render('template', ['values']));

        $this->expectWarning();
        $this->expectWarningMessage('handler restored');
        trigger_error('handler restored', E_USER_WARNING);
    }

    public function testRenderWithViewModel()
    {
        $engine = $this->createMock(Engine::class);
        $engine->method('renderToString')->with('template', ['values'])->willReturn('content');

        $viewModel = new ViewModel(['values']);
        $viewModel->setTemplate('template');

        $templateRenderer = new TemplateRenderer($engine);
        $this->assertEquals('content', $templateRenderer->render($viewModel));

        $this->expectWarning();
        $this->expectWarningMessage('handler restored');
        trigger_error('handler restored', E_USER_WARNING);
    }

    public function testRenderThrowsExceptionOnWarning()
    {
        $engine = $this->createStub(Engine::class);
        $engine->method('renderToString')->willReturnCallback(function () {
            trigger_error('warning', E_USER_WARNING);
        });

        $templateRenderer = new TemplateRenderer($engine);
        try {
            $templateRenderer->render('template');
        } catch (ErrorException $exception) {
            $this->assertEquals('warning', $exception->getMessage());
            $this->assertEquals(E_USER_WARNING, $exception->getSeverity());
        } catch (Throwable $throwable) {
            $this->fail('Expected ErrorException was not thrown');
        }

        $this->expectWarning();
        $this->expectWarningMessage('handler restored');
        trigger_error('handler restored', E_USER_WARNING);
    }

    public function testRenderAcceptsSuppressedWarning()
    {
        $engine = $this->createStub(Engine::class);
        $engine->method('renderToString')->willReturnCallback(function () {
            @trigger_error('warning', E_USER_WARNING);
            return 'success';
        });

        $templateRenderer = new TemplateRenderer($engine);
        $this->assertEquals('success', $templateRenderer->render('template'));

        $this->expectWarning();
        $this->expectWarningMessage('handler restored');
        trigger_error('handler restored', E_USER_WARNING);
    }
}
