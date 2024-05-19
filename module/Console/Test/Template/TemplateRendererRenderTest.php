<?php

namespace Console\Test\Template;

use Console\Template\TemplateRenderer;
use ErrorException;
use Exception;
use Laminas\View\Model\ViewModel;
use Latte\Engine;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\PostCondition;
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
    private const MessagePrefix = 'TEMPLATE RENDERER TEST MESSAGE: ';

    #[Before]
    public function setErrorHandler()
    {
        // Set up a handler that throws a distinct exception which will be
        // caught and tested in assertErrorHandlerRestored().
        set_error_handler(
            function (int $errno, string $errstr) {
                throw new ErrorException(static::MessagePrefix . $errstr, $errno);
            },
            E_USER_WARNING
        );
    }

    #[After]
    public function restoreErrorHandler()
    {
        restore_error_handler();
    }

    #[PostCondition]
    protected function assertErrorHandlerRestored(): void
    {
        // Trigger a warning. The handler that gets installed before each test
        // should throw a distinct exception which will be caught and tested. If
        // render() does not restore the handler, this will not happen, a
        // situtation which will be detected here.
        $message = null;
        try {
            trigger_error('handler restored', E_USER_WARNING);
        } catch (ErrorException $warning) {
            $message = $warning->getMessage();
            if ($message != static::MessagePrefix . 'handler restored') {
                $this->fail('TemplateRenderer::render() did not restore error handler.');
            }
        }
        if (!$message) {
            $this->fail(__CLASS__ . ' set up an error handler, but it did not get invoked.');
        }
    }

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
