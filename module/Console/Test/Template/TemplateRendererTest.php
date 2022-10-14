<?php

namespace Console\Test\Template;

use Console\Service\TemplateRendererFactory;
use Console\Template\TemplateRenderer;
use ErrorException;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\I18n\Translator;
use Latte\Engine;
use Latte\Loaders\FileLoader;
use Library\Application;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Throwable;

class TemplateRendererTest extends TestCase
{
    public function testFactory()
    {
        $translator = $this->createStub(Translator::class);

        /** @var MockObject|ContainerInterface */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(Translator::class)->willReturn($translator);

        $factory = new TemplateRendererFactory();
        $this->assertInstanceOf(TemplateRenderer::class, $factory($container, TemplateRenderer::class));
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
        $translator = $this->createStub(TranslatorInterface::class);
        $templateRenderer = new TemplateRenderer($engine, $translator);
        $this->assertSame($engine, $templateRenderer->getEngine());
    }

    public function testLoader()
    {
        $engine = new Engine();
        $translator = $this->createStub(TranslatorInterface::class);
        $templateRenderer = new TemplateRenderer($engine, $translator);

        $loader = $templateRenderer->getEngine()->getLoader();
        $this->assertInstanceOf(FileLoader::class, $loader);
        $this->assertEquals(Application::getPath('templates'), rtrim($loader->getUniqueId(''), '/'));
    }

    public function testTranslateFunction()
    {
        $engine = new Engine();

        /** @var MockObject|TranslatorInterface */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->with('message')->willReturn('translated');

        $templateRenderer = new TemplateRenderer($engine, $translator);
        $this->assertEquals('translated', $templateRenderer->getEngine()->invokeFunction('translate', ['message']));
    }

    public function testRender()
    {
        $engine = $this->createMock(Engine::class);
        $engine->method('renderToString')->with('template', ['values'])->willReturn('content');

        $translator = $this->createStub(TranslatorInterface::class);

        $templateRenderer = new TemplateRenderer($engine, $translator);
        $this->assertEquals('content', $templateRenderer->render('template', ['values']));

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

        $translator = $this->createStub(TranslatorInterface::class);

        $templateRenderer = new TemplateRenderer($engine, $translator);
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
}
