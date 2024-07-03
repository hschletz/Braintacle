<?php

namespace Console\Test\Template;

use Braintacle\Test\ErrorHandlerTestTrait;
use Console\Template\TemplateRenderer;
use ErrorException;
use Exception;
use Laminas\View\Model\ViewModel;
use Latte\Engine;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Tests for TemplateRenderer::render()
 *
 * The render() method sets a temporary error handler and restores the previous
 * handler when finished. This test class verifies that the restoration has
 * happened after each test.
 */
class TemplateRendererRenderTest extends TestCase
{
    use ErrorHandlerTestTrait;

    public function testRenderWithName()
    {
        $engine = $this->createMock(Engine::class);
        $engine->method('renderToString')->with('template', ['values'])->willReturn('content');

        $templateRenderer = new TemplateRenderer($engine);
        $this->assertEquals('content', $templateRenderer->render('template', ['values']));
    }

    public function testRenderWithViewModel()
    {
        $variables = ['key' => 'value'];

        $engine = $this->createMock(Engine::class);
        $engine->method('renderToString')->with('template', $variables)->willReturn('content');

        $viewModel = new ViewModel($variables);
        $viewModel->setTemplate('template');

        $templateRenderer = new TemplateRenderer($engine);
        $this->assertEquals('content', $templateRenderer->render($viewModel));
    }

    public function testRenderThrowsExceptionOnWarning()
    {
        $engine = $this->createStub(Engine::class);
        $engine->method('renderToString')->willReturnCallback(function () {
            trigger_error('warning', E_USER_WARNING);
        });

        $templateRenderer = new TemplateRenderer($engine);
        try {
            $templateRenderer->render('template'); // Should throw exception
            throw new Exception(); // Fallback, should not be reached, but caught if it will.
        } catch (ErrorException $exception) {
            $this->assertEquals('warning', $exception->getMessage());
            $this->assertEquals(E_USER_WARNING, $exception->getSeverity());
        } catch (Throwable) {
            $this->fail('Expected ErrorException was not thrown');
        }
    }

    #[WithoutErrorHandler]
    public function testRenderAcceptsSuppressedWarning()
    {
        $engine = $this->createStub(Engine::class);
        $engine->method('renderToString')->willReturnCallback(function () {
            @trigger_error('warning', E_USER_WARNING);
            return 'success';
        });

        $templateRenderer = new TemplateRenderer($engine);
        $this->assertEquals('success', $templateRenderer->render('template'));
    }
}
